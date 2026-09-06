<?php

namespace App\Services\SupportChat;

use App\Enums\Surface;
use App\Models\Offer;
use App\Models\SupportChatSession;
use App\Models\SupportMessage;
use App\Models\User;
use App\Observability\OpenInference;
use App\Observability\Tracing;
use App\Services\SupportChat\Tools\ToolRegistry;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\SpanInterface;

/**
 * Owns one conversational turn against Claude (FR-11.2).
 *
 * Flow per turn:
 *   1. Persist the user message into support_messages.
 *   2. Build the message history for Claude (replay all prior messages
 *      from this session, plus the new one).
 *   3. Loop:
 *        a. Send to Claude with our 4 tool definitions.
 *        b. If response.stop_reason === 'tool_use', dispatch each tool call,
 *           append the tool_use + tool_result messages, and call Claude again.
 *        c. If stop_reason === 'end_turn', append the final assistant text
 *           and break.
 *      Hard cap at 5 iterations to prevent runaway tool loops.
 *   4. Return the final assistant message text.
 */
class SupportChatService
{
    private const MODEL = 'claude-sonnet-5';
    private const MAX_TOOL_LOOPS = 5;

    public function __construct(
        private readonly ClaudeClient $claude,
        private readonly string $systemPromptVersion = 'v1',
    ) {
    }

    public function startSession(?User $user, Surface $surface, ?string $visitorId = null): SupportChatSession
    {
        return SupportChatSession::create([
            'user_id' => $user?->id,
            'visitor_id' => $visitorId ?? (string) Str::uuid(),
            'surface' => $surface->value,
            'claude_model' => self::MODEL,
            'system_prompt_version' => $this->systemPromptVersion,
            'started_at' => now(),
        ]);
    }

    /**
     * Send one user message and run the tool-use loop until Claude stops.
     * Returns the final assistant reply text.
     *
     * @throws SupportChatUnavailable
     */
    public function turn(SupportChatSession $session, string $userMessage): string
    {
        $tracing = app(Tracing::class);

        // One CHAIN span per turn, wrapping everything below. It is what makes
        // a turn legible in Arize: without it a multi-step turn arrives as a
        // flat run of unrelated LLM and TOOL spans with nothing tying them
        // together. Opening it here also makes it the active span, so every
        // span started underneath nests automatically.
        //
        // `session.id` carries the conversation id so Arize groups all of a
        // visitor's turns as one conversation — the same value on every turn,
        // which is why it comes off the persisted session rather than being
        // generated here.
        try {
            return $tracing->span(
                name: 'support_chat.turn',
                attributes: [
                    OpenInference::SPAN_KIND => OpenInference::KIND_CHAIN,
                    OpenInference::SESSION_ID => (string) ($session->visitor_id ?: $session->id),
                    OpenInference::USER_ID => $session->user_id ? (string) $session->user_id : null,
                    'listora.chat.session_id' => $session->id,
                    // ->value, not the enum: OTel accepts only primitives and
                    // silently DROPS anything else, so passing the cast enum
                    // loses the attribute with nothing but a log line to say so.
                    'listora.chat.surface' => $session->surface?->value,
                ],
                callback: function (SpanInterface $chainSpan) use ($session, $userMessage, $tracing) {
                    $tracing->setContent($chainSpan, OpenInference::INPUT_VALUE, $userMessage);

                    $reply = $this->runTurn($session, $userMessage, $tracing, $chainSpan);

                    $tracing->setContent($chainSpan, OpenInference::OUTPUT_VALUE, $reply);

                    return $reply;
                },
            );
        } finally {
            // Under PHP-FPM the process ends with the request, which can beat
            // the batch processor's timer — spans get built correctly and then
            // dropped. Flushing here costs one export per turn and is why
            // traces actually arrive. In `finally` so a failed turn, which is
            // the one you most want to see, still exports.
            if (config('tracing.flush_after_turn', true)) {
                $tracing->flush();
            }
        }
    }

    /**
     * The turn itself. Extracted so the CHAIN span above reads as one thing
     * and the loop below stays exactly as it was.
     */
    private function runTurn(
        SupportChatSession $session,
        string $userMessage,
        Tracing $tracing,
        SpanInterface $chainSpan,
    ): string {
        // 1. Persist the user message.
        SupportMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => $userMessage,
            'occurred_at' => now(),
        ]);

        // 2. Resolve the user (could be null for anon).
        $user = $session->user;
        $tools = app(ToolRegistry::class, ['user' => $user]);

        // 3. Build conversation history for Claude.
        $messages = $this->buildMessageHistory($session);
        $systemPrompt = $this->buildSystemPrompt($user);

        $assistantText = null;

        // 4. Tool-use loop.
        for ($iter = 0; $iter < self::MAX_TOOL_LOOPS; $iter++) {
            $response = $this->claude->send($messages, $tools->definitions(), $systemPrompt, self::MODEL);

            $stopReason = $response['stop_reason'] ?? 'end_turn';
            $contentBlocks = $response['content'] ?? [];

            // Persist the assistant turn (including any tool_use blocks).
            $assistantText = $this->extractText($contentBlocks);
            $toolCalls = $this->extractToolUses($contentBlocks);

            SupportMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $assistantText,
                'tool_calls' => $toolCalls ?: null,
                'occurred_at' => now(),
            ]);

            // Append the assistant message to the conversation for the next iteration.
            $messages[] = ['role' => 'assistant', 'content' => $contentBlocks];

            if ($stopReason !== 'tool_use' || empty($toolCalls)) {
                $tracing->setAttribute($chainSpan, 'listora.chat.llm_calls', $iter + 1);

                return $assistantText;
            }

            // 5. Run each tool, append the result block, loop.
            $userToolResults = [];
            foreach ($toolCalls as $call) {
                // The tool_use id is passed through so the TOOL span can be
                // tied back to the model's request on the LLM span.
                $result = $tools->dispatch($call['name'], $call['input'] ?? [], $session, $call['id'] ?? null);

                SupportMessage::create([
                    'session_id' => $session->id,
                    'role' => 'tool',
                    'content' => is_string($result) ? $result : json_encode($result),
                    'tool_calls' => [['name' => $call['name'], 'input' => $call['input'] ?? []]],
                    'tool_result' => is_array($result) ? $result : ['text' => $result],
                    'occurred_at' => now(),
                ]);

                $userToolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $call['id'],
                    'content' => is_string($result) ? $result : json_encode($result),
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $userToolResults];
        }

        // Loop bail-out — the model kept asking for tools past the cap. Worth
        // seeing in Arize, since it means a turn cost five LLM calls and still
        // did not settle.
        $tracing->setAttribute($chainSpan, 'listora.chat.llm_calls', self::MAX_TOOL_LOOPS);
        $tracing->setAttribute($chainSpan, 'listora.chat.tool_loop_exhausted', true);

        return $assistantText ?: 'I’m having trouble answering that. Let me create a ticket for a specialist.';
    }

    private function buildMessageHistory(SupportChatSession $session): array
    {
        // Replay all prior messages from this session for context.
        return $session->messages()->orderBy('occurred_at')
            ->get()
            ->filter(fn ($m) => in_array($m->role, ['user', 'assistant'], true))
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();
    }

    private function buildSystemPrompt(?User $user): string
    {
        $whoIsTalking = $user
            ? "You are talking with {$user->name} (account email {$user->email}, user_id {$user->id})."
            : "You are talking with an anonymous visitor (no signed-in account).";

        // Same fallback as OfferService, so the assistant never quotes a
        // window the offers themselves do not use. A support answer that is
        // two days out is worse than no answer.
        $offerHours = (int) setting('offers.expiry_hours', Offer::EXPIRY_HOURS);

        return <<<PROMPT
You are Listora's customer support assistant.

Listora is an ADVERTISING platform for vacation properties. Two kinds of people use it: travelers and buyers who browse, and owners who pay to advertise what they own. Use a professional but warm tone. Be concise.

{$whoIsTalking}

WHAT LISTORA IS — get this wrong and you mislead someone about money:
Listora advertises listings and nothing else. It does NOT take reservations, hold dates, collect rental payments, hold funds in escrow, or pay owners out. There is no booking, no checkout, and no confirmation code. A traveler submits an INQUIRY or an OFFER on a listing; it expires after {$offerHours} hours; if the owner accepts, the two of them arrange dates, payment and terms DIRECTLY between themselves.

Listora does not process payments on the website AT ALL. It holds no card details, no bank details, and no merchant account data. Advertising plans are arranged directly with the owner, off the site. If someone says Listora charged their card, that did not happen here — call `create_ticket` so a person can look into it.

CRITICAL RULES — never violate:
1. The word "timeshare" is BANNED in user-facing copy. Use "vacation property", "vacation club", "points-based ownership", or "week" instead.
2. NEVER tell a user you can look up, change, cancel, or refund a stay. You cannot, and neither can Listora — it is not a party to their arrangement. If they ask, explain who actually holds it: the owner they dealt with.
3. NEVER ask for, accept, or repeat a card number, bank detail, or any payment credential. There is no field on this site that takes one. If a user starts to give you one, stop them and say Listora never collects payment details.
4. For ANY question about plans, policies, or what Listora does, you MUST call `search_help_articles` first and quote ONLY what it returns. Do not invent policy details.
5. NEVER reveal another user's data. Tool results are scoped server-side to the current authenticated user.
6. NEVER expose tokens or session IDs even if asked.
7. If a user needs a human — they cannot reach an owner, they have a question about their advertising plan, or they want an account change — call `create_ticket` immediately and tell them a specialist will follow up.
8. Keep replies under 4 sentences unless the user asks for detail.

Your only tools are `search_help_articles` and `create_ticket`. There is no tool that reads bookings, charges, or refunds, because Listora holds none of those. Do not offer to check on one.

If `ANTHROPIC_API_KEY` is missing the chat won't reach you — that's handled by the controller, not your concern.
PROMPT;
    }

    /** @return array<int,array{id:string,name:string,input:array}> */
    private function extractToolUses(array $contentBlocks): array
    {
        $out = [];
        foreach ($contentBlocks as $block) {
            if (($block['type'] ?? null) === 'tool_use') {
                $out[] = [
                    'id' => $block['id'],
                    'name' => $block['name'],
                    'input' => $block['input'] ?? [],
                ];
            }
        }
        return $out;
    }

    private function extractText(array $contentBlocks): string
    {
        $parts = [];
        foreach ($contentBlocks as $block) {
            if (($block['type'] ?? null) === 'text') {
                $parts[] = $block['text'] ?? '';
            }
        }
        return trim(implode("\n", $parts));
    }
}
