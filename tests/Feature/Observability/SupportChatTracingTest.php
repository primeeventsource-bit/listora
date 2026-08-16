<?php

namespace Tests\Feature\Observability;

use App\Enums\Surface;
use App\Models\SupportChatSession;
use App\Observability\OpenInference;
use App\Observability\Tracing;
use App\Services\SupportChat\ClaudeClient;
use App\Services\SupportChat\SupportChatService;
use App\Services\SupportChat\TracedClaudeClient;
use ArrayObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenTelemetry\SDK\Trace\ImmutableSpan;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Tests\TestCase;

/**
 * Guards the Arize instrumentation against silent regression.
 *
 * Tracing fails quietly by nature: a dropped attribute, a missing status, or a
 * span that stops nesting produces no error anywhere — it produces a trace
 * that looks fine until someone needs it. So these assertions are deliberately
 * specific about the things that break silently.
 *
 * Arize is never contacted; an in-memory exporter stands in for the OTLP one,
 * which leaves everything except the network hop under test.
 */
class SupportChatTracingTest extends TestCase
{
    use RefreshDatabase;

    private ArrayObject $storage;

    private Tracing $tracing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new ArrayObject();

        // SimpleSpanProcessor, not Batch: spans must be exported the moment
        // they end so assertions can read them without a flush race.
        $this->tracing = new Tracing(
            TracerProvider::builder()
                ->addSpanProcessor(new SimpleSpanProcessor(new InMemoryExporter($this->storage)))
                ->build()
        );

        $this->app->instance(Tracing::class, $this->tracing);
    }

    public function test_a_tool_using_turn_emits_a_nested_chain_llm_and_tool_trace(): void
    {
        $reply = $this->runTurn();

        $this->assertSame('Ownership is verified by our team before a listing goes live.', $reply);

        $spans = $this->spans();
        $this->assertCount(4, $spans, 'Expected one CHAIN, two LLM, and one TOOL span.');

        $chain = $this->spanNamed('support_chat.turn');
        $tool = $this->spanNamed('search_help_articles');
        $llmSpans = array_values(array_filter(
            $spans,
            fn (ImmutableSpan $s) => $s->getName() === 'anthropic.messages',
        ));

        // One trace, and the CHAIN is its root.
        $this->assertCount(1, array_unique(array_map(fn (ImmutableSpan $s) => $s->getTraceId(), $spans)));
        $this->assertFalse($chain->getParentContext()->isValid(), 'CHAIN span should be the trace root.');

        // Everything hangs off the turn. Without this the trace is a flat run
        // of unrelated spans and the turn is unreadable in Arize.
        foreach ([$tool, ...$llmSpans] as $child) {
            $this->assertSame(
                $chain->getSpanId(),
                $child->getParentSpanId(),
                "{$child->getName()} should be a child of the CHAIN span.",
            );
        }

        $this->assertSame(OpenInference::KIND_CHAIN, $chain->getAttributes()->get(OpenInference::SPAN_KIND));
        $this->assertSame(OpenInference::KIND_TOOL, $tool->getAttributes()->get(OpenInference::SPAN_KIND));
        $this->assertSame(OpenInference::KIND_LLM, $llmSpans[0]->getAttributes()->get(OpenInference::SPAN_KIND));
    }

    /**
     * `start_as_current_span` never sets OK for you — a span that sets its
     * attributes and returns exports UNSET, which fails Arize's trace-quality
     * scoring even when every attribute is present.
     */
    public function test_every_span_carries_a_terminal_ok_status(): void
    {
        $this->runTurn();

        foreach ($this->spans() as $span) {
            $this->assertSame(
                'Ok',
                $span->getStatus()->getCode(),
                "{$span->getName()} exported a non-OK status; UNSET fails trace scoring.",
            );
        }
    }

    /**
     * The whole reason a TOOL span exists: an LLM span records the model's
     * *request* to call a tool and never its result. Lose these and a trace
     * shows Claude asking to search and then simply answering.
     */
    public function test_the_tool_span_records_the_full_tool_contract_and_its_result(): void
    {
        $this->runTurn();

        $attrs = $this->spanNamed('search_help_articles')->getAttributes();

        // All three, not just the name — a partial TOOL span fails scoring.
        $this->assertSame('search_help_articles', $attrs->get(OpenInference::TOOL_NAME));
        $this->assertNotEmpty($attrs->get(OpenInference::TOOL_DESCRIPTION));
        $this->assertStringContainsString('query', (string) $attrs->get(OpenInference::TOOL_PARAMETERS));

        // Links the execution back to the model's request on the LLM span.
        $this->assertSame('toolu_verify_1', $attrs->get(OpenInference::TOOL_ID));

        $this->assertStringContainsString('ownership verification', (string) $attrs->get(OpenInference::INPUT_VALUE));
        $this->assertNotEmpty($attrs->get(OpenInference::OUTPUT_VALUE));
    }

    public function test_llm_spans_record_the_prompt_the_tool_request_and_token_counts(): void
    {
        $this->runTurn();

        $first = $this->spansNamed('anthropic.messages')[0]->getAttributes();

        // The system prompt is message 0 — Anthropic carries it outside the
        // messages array, so it would otherwise be missing from every trace.
        $this->assertSame('system', $first->get(OpenInference::inputMessageRole(0)));
        $this->assertStringContainsString('Listora', (string) $first->get(OpenInference::inputMessageContent(0)));
        $this->assertSame('user', $first->get(OpenInference::inputMessageRole(1)));

        $this->assertSame(
            'search_help_articles',
            $first->get(OpenInference::outputMessageToolCallName(0, 0)),
        );

        $this->assertSame(412, $first->get(OpenInference::LLM_TOKEN_COUNT_PROMPT));
        $this->assertSame(55, $first->get(OpenInference::LLM_TOKEN_COUNT_COMPLETION));
        $this->assertSame(467, $first->get(OpenInference::LLM_TOKEN_COUNT_TOTAL));
        $this->assertSame('anthropic', $first->get(OpenInference::LLM_PROVIDER));
    }

    /**
     * Session grouping is what turns individual turns into a conversation in
     * Arize, and `surface` is an enum-cast attribute — OTel drops non-scalars
     * silently, so this asserts the value survived as a string.
     */
    public function test_the_chain_span_carries_session_id_and_scalar_attributes(): void
    {
        $this->runTurn();

        $attrs = $this->spanNamed('support_chat.turn')->getAttributes();

        $this->assertSame('visitor-verify-0001', $attrs->get(OpenInference::SESSION_ID));
        $this->assertSame('web', $attrs->get('listora.chat.surface'));
        $this->assertSame(2, $attrs->get('listora.chat.llm_calls'));
        $this->assertNotEmpty($attrs->get(OpenInference::INPUT_VALUE));
        $this->assertNotEmpty($attrs->get(OpenInference::OUTPUT_VALUE));
    }

    /** With no Arize credentials the chat must behave exactly as it does with them. */
    public function test_tracing_is_a_no_op_and_never_breaks_the_chat_when_unconfigured(): void
    {
        $this->app->instance(Tracing::class, $noop = new Tracing(provider: null));
        $this->assertFalse($noop->isEnabled());

        $session = $this->makeSession();
        $service = new SupportChatService(new TracedClaudeClient($this->stubClaude(), $noop));

        $this->assertSame(
            'Ownership is verified by our team before a listing goes live.',
            $service->turn($session, 'How do you verify ownership?'),
        );
        $this->assertCount(0, $this->storage);
    }

    // ------------------------------------------------------------ helpers

    private function runTurn(): string
    {
        $client = new TracedClaudeClient($this->stubClaude(), $this->tracing);
        $this->app->instance(ClaudeClient::class, $client);

        return (new SupportChatService($client))
            ->turn($this->makeSession(), 'How do you verify that someone owns what they list?');
    }

    private function makeSession(): SupportChatSession
    {
        return SupportChatSession::create([
            'user_id' => null,
            'visitor_id' => 'visitor-verify-0001',
            'surface' => Surface::Web->value,
            'claude_model' => 'claude-sonnet-5',
            'system_prompt_version' => 'v1',
            'started_at' => now(),
        ]);
    }

    /** Claude, scripted: ask for a tool on the first call, answer on the second. */
    private function stubClaude(): ClaudeClient
    {
        return new class implements ClaudeClient
        {
            private int $calls = 0;

            public function send(array $messages, array $tools, string $systemPrompt, string $model): array
            {
                if (++$this->calls === 1) {
                    return [
                        'role' => 'assistant',
                        'stop_reason' => 'tool_use',
                        'content' => [
                            ['type' => 'text', 'text' => 'Let me check the help centre.'],
                            [
                                'type' => 'tool_use',
                                'id' => 'toolu_verify_1',
                                'name' => 'search_help_articles',
                                'input' => ['query' => 'how does ownership verification work'],
                            ],
                        ],
                        'usage' => ['input_tokens' => 412, 'output_tokens' => 55],
                    ];
                }

                return [
                    'role' => 'assistant',
                    'stop_reason' => 'end_turn',
                    'content' => [[
                        'type' => 'text',
                        'text' => 'Ownership is verified by our team before a listing goes live.',
                    ]],
                    'usage' => ['input_tokens' => 690, 'output_tokens' => 32],
                ];
            }
        };
    }

    /** @return list<ImmutableSpan> */
    private function spans(): array
    {
        return array_values((array) $this->storage);
    }

    /** @return list<ImmutableSpan> */
    private function spansNamed(string $name): array
    {
        return array_values(array_filter($this->spans(), fn (ImmutableSpan $s) => $s->getName() === $name));
    }

    private function spanNamed(string $name): ImmutableSpan
    {
        $found = $this->spansNamed($name);
        $this->assertNotEmpty($found, "No span named {$name} was exported.");

        return $found[0];
    }
}
