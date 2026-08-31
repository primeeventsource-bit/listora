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
        /*
        | Defaults to where listora:geoip-update installs the database, so an
        | environment that runs that command in its build gets city-level
        | geolocation without also having to set a path. The binding only uses
        | it when the file is actually readable, so the default is inert until
        | the database exists.
        */
        'mmdb_path' => env('MAXMIND_MMDB_PATH', storage_path('app/geoip/GeoLite2-City.mmdb')),
        'anonymous_mmdb_path' => env('MAXMIND_ANONYMOUS_MMDB_PATH'),
        'disable_cloudflare' => env('GEOIP_DISABLE_CLOUDFLARE', false),

        /*
        | GeoLite2 is free but licensed: it requires an account and a licence
        | key, and MaxMind's terms do not allow redistributing the database.
        | So it is downloaded at build time rather than committed - which also
        | keeps a 60MB binary that goes stale every week out of the repository.
        */
        'license_key' => env('MAXMIND_LICENSE_KEY'),
    ],

    /*
    | Mapbox — the basemap behind the visitor map on Admin -> Reports.
    |
    | Unset is a supported state: the reports page falls back to a plotted
    | grid with the same pins and the same numbers, so local dev and CI never
    | need a token and never call out to a third party. Only the basemap tiles
    | are missing, not the data.
    |
    | Three env names are accepted because the token may already be set under
    | any of them, and Mapbox's own docs use all three across products. The
    | canonical one is MAPBOX_ACCESS_TOKEN; the others are read so an existing
    | deployment does not silently render a blank map because of a name.
    |
    | This is a public (pk.*) token — it is sent to the browser by design, and
    | should be URL-restricted in the Mapbox dashboard rather than kept secret.
    | Never put a secret (sk.*) token here.
    */
    'mapbox' => [
        'token' => env('MAPBOX_ACCESS_TOKEN')
            ?: env('MAPBOX_API_KEY')
            ?: env('MAPBOX_TOKEN'),

        // Style and default view, so changing the look is a config edit rather
        // than a template one.
        'style' => env('MAPBOX_STYLE', 'mapbox://styles/mapbox/light-v11'),
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
