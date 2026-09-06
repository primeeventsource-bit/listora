<?php

namespace App\Services\Settings;

/**
 * The settings catalog: every admin-tunable key, its group, type, default,
 * validation rules, and visibility. This is the ALLOW-LIST — the repository
 * rejects writes to keys not defined here, and the seeder + admin UI render
 * from it, so adding a key here surfaces it everywhere automatically.
 *
 * Conventions:
 *   - type 'percent' = whole-number integer percent (12 = 12%). Never floats.
 *   - type 'encrypted' values are encrypted at rest; 'sensitive' values are
 *     redacted to '••••' in audit payloads and every API/UI response.
 *   - 'public' keys are exposed through GET /api/v1/settings/public.
 *
 * Vaytoven's `payments` and `fees` groups have no counterpart here. Listora
 * takes no payment on the website and takes no commission from what an owner
 * and traveler settle, so there is no processor to select, no routing strategy
 * to tune, no statement descriptor, and no service-fee percentage. An admin
 * console that offered those knobs would describe a business Listora does not
 * operate.
 */
final class SettingsSchema
{
    public const GROUPS = [
        'general' => 'General',
        'listings' => 'Listings',
        'offers' => 'Inquiries & Offers',
        'users' => 'Users',
        'notifications' => 'Notifications',
        'ai_chat' => 'AI Chat',
        'security' => 'Security',
        'integrations' => 'Integrations',
        'seo' => 'SEO',
        'legal' => 'Legal',
    ];

    /** Feature flag keys seeded/managed by this subsystem. */
    public const FEATURE_FLAGS = [
        'offers' => 'Priced offers on listings (as distinct from plain inquiries).',
        'wishlists' => 'Traveler wishlists (save listings).',
        'world_map' => 'Interactive destination map on browse surfaces.',
        'ai_chat' => 'AI support chat widget and /support/chat endpoint.',
        'listing_wizard' => 'The public "list your property" wizard.',
        'owner_registration' => 'New owner sign-ups.',
        'sms_notifications' => 'Outbound SMS notifications (requires an SMS provider).',
    ];

    /** Flags seeded disabled (everything else defaults on). */
    public const FLAGS_DEFAULT_OFF = [
        'sms_notifications',
    ];

    private static ?array $catalog = null;

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::catalog());
    }

    public static function spec(string $key): ?array
    {
        return self::catalog()[$key] ?? null;
    }

    /** @return array<string, array> specs for one group, in sort order */
    public static function group(string $group): array
    {
        return array_filter(self::catalog(), fn (array $spec) => $spec['group'] === $group);
    }

    /** Laravel validation rules for a key (beyond the type cast itself). */
    public static function rules(string $key): array
    {
        return self::catalog()[$key]['rules'] ?? [];
    }

    public static function defaultFor(string $key): mixed
    {
        return self::catalog()[$key]['default'] ?? null;
    }

    /**
     * key => spec. Spec keys: group, type, default, label, rules, help,
     * public, sensitive, options (enum only). Omitted booleans are false.
     */
    public static function catalog(): array
    {
        return self::$catalog ??= self::build();
    }

    private static function build(): array
    {
        $defs = [
            // ---------------------------------------------------------- general
            'general.site_name' => ['general', 'string', 'Listora', 'Site name', ['string', 'max:120'], 'Shown in titles and emails.', true],
            'general.tagline' => ['general', 'string', 'Where owners and travelers meet directly.', 'Tagline', ['nullable', 'string', 'max:160'], null, true],
            'general.logo_url' => ['general', 'string', '', 'Logo URL', ['nullable', 'string', 'max:512'], null, true],
            'general.favicon_url' => ['general', 'string', '', 'Favicon URL', ['nullable', 'string', 'max:512'], null, true],
            'general.support_email' => ['general', 'string', 'hello@listora.com', 'Support email', ['nullable', 'email', 'max:255'], null, true],
            'general.support_phone' => ['general', 'string', '(800) 555-0142', 'Support phone', ['nullable', 'string', 'max:32'], null, true],
            'general.company_legal_name' => ['general', 'string', 'Listora LLC', 'Company legal name', ['string', 'max:160']],
            'general.timezone' => ['general', 'string', 'America/New_York', 'Timezone', ['timezone:all']],
            'general.default_locale' => ['general', 'enum', 'en', 'Default locale', ['in:en'], null, false, false, ['en']],
            'general.default_currency' => ['general', 'enum', 'USD', 'Display currency', ['in:USD,EUR,GBP,CAD,MXN'], 'Formats asking prices for display. Listora moves no money.', false, false, ['USD', 'EUR', 'GBP', 'CAD', 'MXN']],
            'general.maintenance_mode' => ['general', 'bool', false, 'Maintenance mode', ['boolean'], 'Non-admin visitors see the maintenance message; admins keep full access.', true],
            'general.maintenance_message' => ['general', 'text', 'We are briefly down for maintenance and will be right back.', 'Maintenance message', ['nullable', 'string', 'max:2000'], null, true],
            'general.reference_prefix' => ['general', 'string', 'LST-', 'Reference code prefix', ['string', 'max:8'], 'Display/validation only — code generation stays server-authoritative.'],
            'general.social_instagram' => ['general', 'string', '', 'Instagram URL', ['nullable', 'string', 'max:255'], null, true],
            'general.social_facebook' => ['general', 'string', '', 'Facebook URL', ['nullable', 'string', 'max:255'], null, true],
            'general.social_tiktok' => ['general', 'string', '', 'TikTok URL', ['nullable', 'string', 'max:255'], null, true],
            'general.social_x' => ['general', 'string', '', 'X (Twitter) URL', ['nullable', 'string', 'max:255'], null, true],
            'general.social_youtube' => ['general', 'string', '', 'YouTube URL', ['nullable', 'string', 'max:255'], null, true],

            // ---------------------------------------------------------- listings
            'listings.approval_mode' => ['listings', 'enum', 'manual', 'Listing approval', ['in:auto,manual,first_only'], 'first_only reviews an owner\'s first listing manually, then auto-approves.', false, false, ['auto', 'manual', 'first_only']],
            'listings.require_ownership_verification' => ['listings', 'bool', true, 'Require ownership verification before publishing', ['boolean'], 'The promise every plan makes. Turning this off publishes unverified listings.'],
            'listings.min_photos' => ['listings', 'int', 4, 'Minimum photos', ['integer', 'between:0,30']],
            'listings.default_term_days' => ['listings', 'int', 180, 'Default advertising term (days)', ['integer', 'between:1,1095']],
            'listings.expiry_warning_days' => ['listings', 'int', 30, 'Warn owner before term expiry (days)', ['integer', 'between:0,120']],
            'listings.auto_expire' => ['listings', 'bool', true, 'Automatically expire listings at end of term', ['boolean']],
            'listings.default_region' => ['listings', 'string', '', 'Default region slug', ['nullable', 'string', 'max:64']],

            // ---------------------------------------------------------- offers
            'offers.enabled' => ['offers', 'bool', true, 'Accept offers and inquiries', ['boolean'], null, true],
            'offers.expiry_hours' => ['offers', 'int', 24, 'Hours before an open offer expires', ['integer', 'between:1,720']],
            'offers.require_account' => ['offers', 'bool', false, 'Require an account to submit', ['boolean'], 'Off means anonymous visitors may contact an owner.'],
            'offers.notify_owner_email' => ['offers', 'bool', true, 'Email the owner on each submission', ['boolean']],
            'offers.max_per_day_per_ip' => ['offers', 'int', 20, 'Submissions per IP per day', ['integer', 'between:1,500']],

            // ---------------------------------------------------------- users
            'users.registration_open' => ['users', 'bool', true, 'Open registration', ['boolean'], null, true],
            'users.require_email_verification' => ['users', 'bool', true, 'Require email verification', ['boolean']],
            'users.owner_requires_approval' => ['users', 'bool', true, 'New owners require ops approval', ['boolean']],
            'users.min_password_length' => ['users', 'int', 12, 'Minimum password length', ['integer', 'between:8,128']],
            'users.allow_social_login' => ['users', 'bool', false, 'Allow social login', ['boolean']],

            // ---------------------------------------------------------- notifications
            'notifications.from_name' => ['notifications', 'string', 'Listora', 'From name', ['string', 'max:120']],
            'notifications.from_email' => ['notifications', 'string', 'no-reply@listora.com', 'From email', ['email', 'max:255']],
            'notifications.email_enabled' => ['notifications', 'bool', true, 'Email notifications', ['boolean']],
            'notifications.sms_enabled' => ['notifications', 'bool', false, 'SMS notifications', ['boolean'], 'Also requires the sms_notifications feature flag and a provider.'],
            'notifications.retention_days' => ['notifications', 'int', 90, 'In-app notification retention (days)', ['integer', 'between:7,3650']],
            'notifications.channel_matrix' => ['notifications', 'json', [
                'inquiry_received' => ['email'],
                'offer_received' => ['email'],
                'listing_published' => ['email'],
                'listing_expiring' => ['email'],
            ], 'Channel matrix', ['array'], 'Per event: which of email/sms fire.'],

            // ---------------------------------------------------------- ai_chat
            'ai_chat.enabled' => ['ai_chat', 'bool', true, 'AI chat enabled', ['boolean'], 'Also gated by the ai_chat feature flag.', true],
            'ai_chat.model' => ['ai_chat', 'string', 'claude-sonnet-5', 'Model', ['string', 'max:64']],
            'ai_chat.system_prompt' => ['ai_chat', 'text', '', 'System prompt override', ['nullable', 'string', 'max:20000'], 'Blank = the built-in support prompt.'],
            'ai_chat.max_turns_before_handoff' => ['ai_chat', 'int', 6, 'Max turns before human handoff', ['integer', 'between:1,50']],
            'ai_chat.business_hours_only' => ['ai_chat', 'bool', false, 'Business hours only', ['boolean']],
            'ai_chat.rate_limit_per_session' => ['ai_chat', 'int', 40, 'Messages per session', ['integer', 'between:1,500']],
            'ai_chat.api_key' => ['ai_chat', 'encrypted', '', 'Anthropic API key override', ['nullable', 'string', 'max:255'], 'Blank = use ANTHROPIC_API_KEY from the environment.', false, true],

            // ---------------------------------------------------------- security
            'security.session_timeout_min' => ['security', 'int', 120, 'Session timeout (minutes)', ['integer', 'between:5,1440']],
            'security.admin_2fa_required' => ['security', 'bool', true, 'Require 2FA for admins', ['boolean']],
            'security.admin_ip_allowlist' => ['security', 'json', [], 'Admin IP allowlist', ['array'], 'Empty = allow all.'],
            'security.login_anomaly_threshold' => ['security', 'int', 3, 'Login anomaly threshold', ['integer', 'between:1,20'], 'Failed attempts before an account is flagged.'],
            'security.geoip_provider' => ['security', 'enum', 'maxmind', 'GeoIP provider', ['in:maxmind,cloudflare,none'], null, false, false, ['maxmind', 'cloudflare', 'none']],
            'security.terms_reaccept_on_new_version' => ['security', 'bool', true, 'Force re-acceptance on new terms version', ['boolean']],
            'security.password_reset_ttl_min' => ['security', 'int', 60, 'Password reset link TTL (minutes)', ['integer', 'between:10,1440']],

            // ---------------------------------------------------------- integrations
            'integrations.mail_transport' => ['integrations', 'enum', 'smtp', 'Mail transport', ['in:smtp,ses,postmark,resend'], null, false, false, ['smtp', 'ses', 'postmark', 'resend']],
            'integrations.smtp_host' => ['integrations', 'string', '', 'SMTP host', ['nullable', 'string', 'max:255']],
            'integrations.smtp_port' => ['integrations', 'int', 587, 'SMTP port', ['integer', 'between:1,65535']],
            'integrations.smtp_username' => ['integrations', 'string', '', 'SMTP username', ['nullable', 'string', 'max:255']],
            'integrations.smtp_password' => ['integrations', 'encrypted', '', 'SMTP password', ['nullable', 'string', 'max:255'], null, false, true],
            'integrations.sms_provider' => ['integrations', 'enum', 'none', 'SMS provider', ['in:twilio,none'], null, false, false, ['twilio', 'none']],
            'integrations.twilio_sid' => ['integrations', 'string', '', 'Twilio SID', ['nullable', 'string', 'max:64']],
            'integrations.twilio_token' => ['integrations', 'encrypted', '', 'Twilio auth token', ['nullable', 'string', 'max:255'], null, false, true],
            'integrations.maxmind_license_key' => ['integrations', 'encrypted', '', 'MaxMind license key', ['nullable', 'string', 'max:255'], null, false, true],
            'integrations.analytics_id' => ['integrations', 'string', '', 'Analytics ID', ['nullable', 'string', 'max:64'], 'GA4 measurement ID (G-XXXXXXX). Blank loads no analytics at all.', true],
            'integrations.google_ads_id' => ['integrations', 'string', '', 'Google Ads conversion ID', ['nullable', 'string', 'max:64'], 'AW-XXXXXXXXX. Enables remarketing audiences and conversion linking on paid landing pages.', true],
            'integrations.slack_alerts_webhook' => ['integrations', 'encrypted', '', 'Slack alerts webhook', ['nullable', 'string', 'max:512'], null, false, true],

            // ---------------------------------------------------------- seo
            'seo.meta_title_default' => ['seo', 'string', 'Listora — vacation properties, club points & weeks, advertised direct', 'Default meta title', ['string', 'max:160'], null, true],
            'seo.meta_description_default' => ['seo', 'text', 'Browse vacation properties advertised directly by their owners. Listora never sits in the middle of the conversation.', 'Default meta description', ['string', 'max:320'], null, true],
            'seo.og_image_url' => ['seo', 'string', '', 'Open Graph image URL', ['nullable', 'string', 'max:512'], null, true],
            'seo.robots_index' => ['seo', 'bool', true, 'Allow search indexing', ['boolean'], null, true],
            'seo.sitemap_enabled' => ['seo', 'bool', true, 'Sitemap enabled', ['boolean']],
            'seo.featured_destinations' => ['seo', 'json', [], 'Featured destinations', ['array'], null, true],

            // ---------------------------------------------------------- legal
            'legal.terms_version_id' => ['legal', 'int', 0, 'Pinned terms version id', ['integer', 'min:0'], '0 = latest effective version from terms_versions.'],
            'legal.privacy_url' => ['legal', 'string', '/legal/privacy', 'Privacy policy URL', ['string', 'max:512'], null, true],
        ];

        $catalog = [];
        $sort = 0;
        foreach ($defs as $key => $def) {
            $catalog[$key] = [
                'group' => $def[0],
                'type' => $def[1],
                'default' => $def[2],
                'label' => $def[3],
                'rules' => $def[4] ?? [],
                'help' => $def[5] ?? null,
                'public' => $def[6] ?? false,
                'sensitive' => ($def[7] ?? false) || $def[1] === 'encrypted',
                'options' => $def[8] ?? null,
                'sort' => $sort += 10,
            ];
        }

        return $catalog;
    }
}
