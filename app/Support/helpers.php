<?php

use App\Models\User;
use App\Services\Settings\FeatureFlagService;
use App\Services\Settings\SettingsRepository;

if (! function_exists('setting')) {
    /**
     * Read an admin-tunable setting (cache -> DB -> SettingsSchema default).
     *
     *   setting('fees.guest_service_pct', 12)   // int percent
     *   setting('general.maintenance_mode')     // bool
     *
     * Unknown keys return $default — business code should only ever pass
     * keys defined in SettingsSchema.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsRepository::class)->get($key, $default);
    }
}

if (! function_exists('feature')) {
    /**
     * Resolve a feature flag, honoring scope + rollout percentage.
     * Missing flag rows resolve to $default (fail-open for existing
     * functionality on environments where the seeder hasn't run).
     */
    function feature(string $key, ?User $user = null, bool $default = true): bool
    {
        $user ??= auth()->user();

        return app(FeatureFlagService::class)->enabled($key, $user, $default);
    }
}

if (! function_exists('asset_v')) {
    /**
     * asset(), with a cache-busting version derived from the file's mtime.
     *
     * Static CSS and JS are served with `Cache-Control: public, max-age=14400`
     * and sit behind Cloudflare, so a plain asset() URL means a stylesheet
     * change is invisible for up to four hours at the edge and longer in a
     * warm browser cache. A deploy would appear to have done nothing, which
     * is worse than an obvious failure because the natural next move is to
     * change the code again.
     *
     * The mtime changes on every deploy that touches the file and on no
     * deploy that does not, so the URL is stable exactly as long as the file
     * is. Falls back to the unversioned path if the file cannot be stat'd -
     * a missing asset should 404 loudly rather than be masked by an error
     * here.
     */
    function asset_v(string $path): string
    {
        $full = public_path($path);
        $stamp = is_file($full) ? @filemtime($full) : false;

        return $stamp === false
            ? asset($path)
            : asset($path).'?v='.$stamp;
    }
}
