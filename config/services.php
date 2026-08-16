<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    | Everything the container reads must be declared here, not read from
    | env() at the point of use. Once `config:cache` runs — and Laravel Cloud
    | runs it on every deploy — env() returns null everywhere outside this
    | directory, so a service configured that way silently disables itself in
    | production and works perfectly in local dev.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Anthropic — powers the Help page's support assistant.
    |
    | Unset is a supported state: SupportChatController returns a graceful
    | "temporarily unavailable" and points the visitor at email rather than
    | erroring.
    */
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
    ],

    /*
    | MaxMind GeoIP2 — IP geolocation for login security and tracking.
    |
    | Resolution order in AppServiceProvider is MaxMind (when mmdb_path points
    | at a readable file) -> Cloudflare headers -> no-op. Set
    | `disable_cloudflare` to skip straight to the no-op.
    */
    'maxmind' => [
        'mmdb_path' => env('MAXMIND_MMDB_PATH'),
        'anonymous_mmdb_path' => env('MAXMIND_ANONYMOUS_MMDB_PATH'),
        'disable_cloudflare' => env('GEOIP_DISABLE_CLOUDFLARE', false),
    ],

    'slack' => [
        // Ops alerts. Unset binds NoOpSlackNotifier so dev and CI run clean.
        'ops_webhook_url' => env('SLACK_OPS_WEBHOOK_URL'),

        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
