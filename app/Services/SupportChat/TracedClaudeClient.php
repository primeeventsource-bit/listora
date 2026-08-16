<?php

namespace App\Services\SupportChat;

use App\Observability\OpenInference;
use App\Observability\Tracing;
use OpenTelemetry\API\Trace\SpanInterface;

/**
 * Emits the LLM span for each call to Anthropic's Messages API.
 *
 * Written as a decorator around ClaudeClient rather than as instrumentation
 * inside AnthropicClaudeClient so the production client keeps doing exactly
 * one thing. Tracing is additive: swap the binding and the traced behaviour
 * disappears without a line of the HTTP client changing.
 *
 * Arize's auto-instrumentors do not reach here — they exist for Python, TS/JS,
 * Java and Go, and this is PHP calling the REST API directly — so every
 * OpenInference attribute on the span is set by hand.
 *
 * What this span deliberately does NOT record: the tools' *execution* or their
 * return values. An LLM span sees the model's *request* to call a tool and
 * nothing more; the result is recorded by ToolRegistry's TOOL span. Recording
 * it in both places would double-count it and misattribute where time went.
 */
class TracedClaudeClient implements ClaudeClient
{
    public function __construct(
        private readonly ClaudeClient $inner,
        private readonly Tracing $tracing,
    ) {
    }

    public function send(array $messages, array $tools, string $systemPrompt, string $model): array
    {
        return $this->tracing->span(
            name: 'anthropic.messages',
            attributes: [
                OpenInference::SPAN_KIND => OpenInference::KIND_LLM,
                OpenInference::LLM_MODEL_NAME => $model,
                OpenInference::LLM_PROVIDER => 'anthropic',
                OpenInference::LLM_SYSTEM => 'anthropic',
            ],
            callback: function (SpanInterface $span) use ($messages, $tools, $systemPrompt, $model) {
                $this->recordRequest($span, $messages, $tools, $systemPrompt);

                // A failure here propagates, so Tracing::span marks the span
                // ERROR and records the exception. Nothing to catch.
                $response = $this->inner->send($messages, $tools, $systemPrompt, $model);

                $this->recordResponse($span, $response);

                return $response;
            },
        );
    }

    /**
     * The system prompt is message 0.
     *
     * Anthropic carries it in its own top-level `system` field rather than in
     * the messages array, but OpenInference expects the full prompt as
     * indexed input messages — omitting it would leave every trace missing the
     * instructions that actually shaped the answer, which is the first thing
     * anyone debugging a bad reply wants to read.
     */
    private function recordRequest(SpanInterface $span, array $messages, array $tools, string $systemPrompt): void
    {
        $this->tracing->setAttribute($span, OpenInference::inputMessageRole(0), 'system');
        $this->tracing->setContent($span, OpenInference::inputMessageContent(0), $systemPrompt);

        foreach (array_values($messages) as $i => $message) {
            $index = $i + 1;
            $this->tracing->setAttribute($span, OpenInference::inputMessageRole($index), (string) ($message['role'] ?? 'user'));
            $this->tracing->setContent($span, OpenInference::inputMessageContent($index), $this->flatten($message['content'] ?? ''));
        }

        // The last user turn, as the span's headline input.
        $this->tracing->setContent($span, OpenInference::INPUT_VALUE, $this->lastUserText($messages) ?? $systemPrompt);

        if ($tools !== []) {
            $this->tracing->setAttribute(
                $span,
                'llm.tools',
                $this->tracing->encode(array_map(
                    fn (array $t) => ['name' => $t['name'] ?? null, 'description' => $t['description'] ?? null],
                    $tools,
                )),
            );
        }
    }

    private function recordResponse(SpanInterface $span, array $response): void
    {
        $this->tracing->setAttribute($span, OpenInference::outputMessageRole(0), (string) ($response['role'] ?? 'assistant'));

        $text = [];
        $toolCallIndex = 0;

        foreach ($response['content'] ?? [] as $block) {
            $type = $block['type'] ?? null;

            if ($type === 'text') {
                $text[] = (string) ($block['text'] ?? '');

                continue;
            }

            if ($type === 'tool_use') {
                // The model's *request* to call a tool. Its execution is the
                // TOOL span's business, not this one's.
                $this->tracing->setAttribute(
                    $span,
                    OpenInference::outputMessageToolCallName(0, $toolCallIndex),
                    (string) ($block['name'] ?? ''),
                );
                $this->tracing->setContent(
                    $span,
                    OpenInference::outputMessageToolCallArguments(0, $toolCallIndex),
                    $this->tracing->encode($block['input'] ?? []),
                );
                $toolCallIndex++;
            }
        }

        $joined = trim(implode("\n", $text));
        $this->tracing->setContent($span, OpenInference::outputMessageContent(0), $joined);
        $this->tracing->setContent($span, OpenInference::OUTPUT_VALUE, $joined);

        $this->tracing->setAttribute($span, 'llm.stop_reason', (string) ($response['stop_reason'] ?? ''));

        // Token counts are what make cost and latency analysis possible in
        // Arize, and they only ever appear on the API response.
        $usage = $response['usage'] ?? [];
        $prompt = (int) ($usage['input_tokens'] ?? 0);
        $completion = (int) ($usage['output_tokens'] ?? 0);

        if ($prompt || $completion) {
            $this->tracing->setAttribute($span, OpenInference::LLM_TOKEN_COUNT_PROMPT, $prompt);
            $this->tracing->setAttribute($span, OpenInference::LLM_TOKEN_COUNT_COMPLETION, $completion);
            $this->tracing->setAttribute($span, OpenInference::LLM_TOKEN_COUNT_TOTAL, $prompt + $completion);
        }
    }

    /** Anthropic content is either a string or an array of typed blocks. */
    private function flatten(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (! is_array($content)) {
            return (string) $content;
        }

        $parts = [];
        foreach ($content as $block) {
            $parts[] = match ($block['type'] ?? null) {
                'text' => (string) ($block['text'] ?? ''),
                'tool_use' => '[tool_use '.($block['name'] ?? '?').' '.$this->tracing->encode($block['input'] ?? []).']',
                'tool_result' => '[tool_result '.$this->tracing->encode($block['content'] ?? '').']',
                default => $this->tracing->encode($block),
            };
        }

        return implode("\n", array_filter($parts));
    }

    private function lastUserText(array $messages): ?string
    {
        foreach (array_reverse($messages) as $message) {
            if (($message['role'] ?? null) === 'user') {
                $flat = trim($this->flatten($message['content'] ?? ''));

                if ($flat !== '') {
                    return $flat;
                }
            }
        }

        return null;
    }
}
