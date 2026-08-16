<?php

namespace App\Observability;

use Closure;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Throwable;

/**
 * Thin wrapper over the OpenTelemetry tracer, with the OpenInference rules
 * that are easy to get wrong baked in.
 *
 * Two of those rules are why this class exists rather than call sites using
 * the OTel API directly:
 *
 *  1. **A span must end with a terminal status.** `StatusCode::STATUS_OK` is
 *     never set for you — a span that sets its attributes and returns exports
 *     `UNSET`, which fails Arize's trace-quality scoring even when every
 *     attribute is present. `span()` always sets it.
 *  2. **A caught error still needs `ERROR` set explicitly.** The support chat
 *     catches tool failures and feeds them back to the model, so nothing
 *     propagates out of the span for OTel to notice. `failed()` exists for
 *     that case.
 *
 * Every method degrades to a no-op when tracing is unconfigured, so the
 * support chat behaves identically with or without Arize credentials. Tracing
 * must never be able to break the thing it observes.
 */
class Tracing
{
    private const INSTRUMENTATION_SCOPE = 'listora.support-chat';

    public function __construct(
        private readonly ?TracerProvider $provider,
        private readonly bool $captureContent = true,
        private readonly int $maxValueLength = 8000,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->provider !== null;
    }

    public function tracer(): ?TracerInterface
    {
        return $this->provider?->getTracer(self::INSTRUMENTATION_SCOPE);
    }

    /**
     * Run $callback inside a span, with correct terminal status either way.
     *
     * The span is made the active span for the duration, so any span started
     * inside $callback nests underneath it automatically — that is what makes
     * the LLM and TOOL spans children of the CHAIN span without anything
     * having to pass a parent around.
     *
     * @template T
     *
     * @param  array<string, mixed>  $attributes
     * @param  Closure(SpanInterface): T  $callback
     * @return T
     */
    public function span(string $name, array $attributes, Closure $callback): mixed
    {
        $tracer = $this->tracer();

        if ($tracer === null) {
            // Pass a non-recording span so callers can set attributes on it
            // unconditionally without a null check at every line.
            return $callback(\OpenTelemetry\API\Trace\Span::getInvalid());
        }

        $span = $tracer->spanBuilder($name)->startSpan();

        foreach ($attributes as $key => $value) {
            $this->setAttribute($span, $key, $value);
        }

        $scope = $span->activate();

        try {
            $result = $callback($span);

            // THE most-forgotten line. Without it the span exports UNSET.
            // Only set OK if the callback didn't already mark it ERROR.
            if ($span->isRecording()) {
                $span->setStatus(StatusCode::STATUS_OK);
            }

            return $result;
        } catch (Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());

            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Mark a span failed for an error that was caught and handled.
     *
     * Needed because an exception the application swallows never reaches the
     * span, so without this the failure exports as a successful span.
     */
    public function failed(SpanInterface $span, Throwable|string $error): void
    {
        if ($error instanceof Throwable) {
            $span->recordException($error);
            $span->setStatus(StatusCode::STATUS_ERROR, $error->getMessage());

            return;
        }

        $span->setStatus(StatusCode::STATUS_ERROR, $error);
    }

    /**
     * Set an attribute, truncating long values and honouring the
     * content-capture switch for anything user-authored.
     */
    public function setAttribute(SpanInterface $span, string $key, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        if (is_string($value)) {
            $value = $this->truncate($value);
        }

        $span->setAttribute($key, $value);
    }

    /**
     * Set a content-bearing attribute (message bodies, tool results).
     *
     * Routed separately from setAttribute so `capture_content=false` can drop
     * message text while keeping span structure, timings, and token counts.
     */
    public function setContent(SpanInterface $span, string $key, mixed $value): void
    {
        if (! $this->captureContent) {
            return;
        }

        $this->setAttribute($span, $key, is_string($value) ? $value : $this->encode($value));
    }

    /** JSON for span attributes: readable in the Arize UI, never escaped slashes. */
    public function encode(mixed $value): string
    {
        return $this->truncate(
            json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?: ''
        );
    }

    /**
     * Ship buffered spans now.
     *
     * PHP-FPM ends the process at the end of a request, which can happen
     * before the batch processor's timer fires — spans would be built
     * correctly and then dropped on the floor.
     */
    public function flush(): void
    {
        $this->provider?->forceFlush();
    }

    private function truncate(string $value): string
    {
        return mb_strlen($value) > $this->maxValueLength
            ? mb_substr($value, 0, $this->maxValueLength).'… [truncated]'
            : $value;
    }
}
