<?php

/*
|--------------------------------------------------------------------------
| Arize AX tracing
|--------------------------------------------------------------------------
|
| Listora's one LLM surface is the support chat, which calls Anthropic's
| Messages API directly and runs its own tool loop. Arize publishes no PHP
| integration (its routing covers Python, TS/JS, Java and Go), so tracing here
| is hand-rolled on the OpenTelemetry PHP SDK against OpenInference semantic
| conventions — see App\Observability\OpenInference.
|
| Tracing is entirely optional. With no credentials set, the tracer provider
| is never built and every span helper becomes a no-op, so a missing key can
| never take the support chat down.
|
| Credentials come from the environment and only from the environment. Set
| them in your own .env — they must never be committed or pasted into a chat:
|
|   ARIZE_SPACE_ID   base64 Space ID (NOT the space name — `ax spaces list`)
|   ARIZE_API_KEY    app.arize.com -> Settings -> API Keys (scoped service key)
|
*/

return [

    /*
    | Master switch. Tracing also stays off whenever space_id or api_key is
    | blank, so this only needs setting to disable an otherwise-configured
    | environment.
    */
    'enabled' => env('ARIZE_TRACING_ENABLED', true),

    /*
    | Arize requires a project name — exporting without one fails with a 500,
    | and `service.name` alone does not satisfy it. Sent as the
    | `openinference.project.name` and `model_id` resource attributes.
    */
    'project_name' => env('ARIZE_PROJECT_NAME', 'listora-support-chat'),

    /*
    | Collector endpoint. Region is NOT assumed: this default is the US
    | cluster and is stated as such. Override for EU or Canada:
    |
    |   US      https://otlp.arize.com/v1/traces
    |   EU      https://otlp.eu-west-1a.arize.com/v1/traces
    |   Canada  https://otlp.ca-central-1a.arize.com/v1/traces
    |
    | This is the signal-specific path for the OTLP *HTTP* exporter, which is
    | what this app uses — ext-grpc is not installed, so the gRPC transport is
    | unavailable.
    */
    'endpoint' => env('ARIZE_COLLECTOR_ENDPOINT', 'https://otlp.arize.com/v1/traces'),

    'space_id' => env('ARIZE_SPACE_ID'),
    'api_key' => env('ARIZE_API_KEY'),

    /*
    | PHP-FPM handles one request per process and the batch processor ships on
    | a timer, so a request can finish before its spans leave the buffer. The
    | provider is force-flushed at the end of each traced turn instead. Set
    | this false only if you are running under a long-lived worker (Octane)
    | where the batch timer gets a chance to fire on its own.
    */
    'flush_after_turn' => env('ARIZE_FLUSH_AFTER_TURN', true),

    /*
    | Message content is the point of LLM tracing — you cannot debug a bad
    | reply you cannot read. But it is also user-typed free text, so an
    | operator who needs spans without content can turn the bodies off and
    | keep the structure, timings, token counts, and status.
    */
    'capture_content' => env('ARIZE_CAPTURE_CONTENT', true),

    /*
    | Hard cap on any single captured value, in characters. A long help
    | article pasted into a tool result would otherwise be sent verbatim on
    | every span.
    */
    'max_value_length' => (int) env('ARIZE_MAX_VALUE_LENGTH', 8000),

];
