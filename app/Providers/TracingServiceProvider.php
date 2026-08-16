<?php

namespace App\Providers;

use App\Observability\Tracing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\Contrib\Otlp\ContentTypes;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Throwable;

/**
 * Builds the OpenTelemetry tracer provider that exports Listora's support-chat
 * spans to Arize AX.
 *
 * Arize publishes no PHP integration — its routing covers Python, TS/JS, Java
 * and Go — so this is the manual-instrumentation path: the vendor-neutral
 * OpenTelemetry PHP SDK, an OTLP/HTTP exporter pointed at Arize's collector,
 * and OpenInference attributes set by hand on each span.
 *
 * Transport is HTTP rather than gRPC because `ext-grpc` is not installed;
 * `open-telemetry/transport-grpc` cannot even be required without it.
 *
 * Registered as a singleton so one provider (and one batch processor) is
 * shared for the life of the process rather than rebuilt per resolution.
 */
class TracingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Tracing::class, function () {
            $config = config('tracing');

            return new Tracing(
                provider: $this->buildProvider($config),
                captureContent: (bool) ($config['capture_content'] ?? true),
                maxValueLength: (int) ($config['max_value_length'] ?? 8000),
            );
        });
    }

    /**
     * Returns null whenever tracing cannot be configured, which makes every
     * Tracing method a no-op.
     *
     * Failing soft is deliberate. This instruments a customer-facing support
     * chat, and an observability misconfiguration must never be able to take
     * that chat down — a missing API key should cost visibility, not service.
     */
    private function buildProvider(array $config): ?TracerProvider
    {
        if (! ($config['enabled'] ?? false)) {
            return null;
        }

        $spaceId = (string) ($config['space_id'] ?? '');
        $apiKey = (string) ($config['api_key'] ?? '');

        // No credentials is the normal state for local dev and CI, so it is
        // not worth a warning. Half-configured is a genuine mistake and is.
        if ($spaceId === '' && $apiKey === '') {
            return null;
        }

        if ($spaceId === '' || $apiKey === '') {
            Log::warning('Arize tracing is half-configured; traces will not be exported.', [
                'has_space_id' => $spaceId !== '',
                'has_api_key' => $apiKey !== '',
            ]);

            return null;
        }

        $projectName = trim((string) ($config['project_name'] ?? ''));

        // Arize rejects an export with no project name — a 500 from the
        // collector, long after the misconfiguration. `service.name` alone
        // does not satisfy it, so catch it here where the cause is obvious.
        if ($projectName === '') {
            Log::warning('Arize tracing needs a project name (ARIZE_PROJECT_NAME); tracing disabled.');

            return null;
        }

        try {
            $transport = (new OtlpHttpTransportFactory())->create(
                endpoint: (string) $config['endpoint'],
                contentType: ContentTypes::PROTOBUF,
                headers: [
                    // Arize's OTLP collector authenticates on these two
                    // headers. Values come from the environment only.
                    'space_id' => $spaceId,
                    'api_key' => $apiKey,
                ],
            );

            $resource = ResourceInfoFactory::defaultResource()->merge(
                ResourceInfo::create(Attributes::create([
                    // Arize reads the project from these. Both are set
                    // because the collector has accepted each historically
                    // and neither is costly to send.
                    'openinference.project.name' => $projectName,
                    'model_id' => $projectName,
                    'service.name' => $projectName,
                    'service.version' => (string) config('app.version', '1.0.0'),
                    'deployment.environment' => (string) config('app.env'),
                ]))
            );

            return TracerProvider::builder()
                ->addSpanProcessor(new BatchSpanProcessor(new SpanExporter($transport), null))
                ->setResource($resource)
                ->build();
        } catch (Throwable $e) {
            Log::warning('Arize tracing failed to initialise: '.$e->getMessage(), ['exception' => $e]);

            return null;
        }
    }
}
