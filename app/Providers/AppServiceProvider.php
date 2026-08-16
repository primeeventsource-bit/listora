<?php

namespace App\Providers;

use App\Models\User;
use App\Observability\Tracing;
use App\Services\GeoIp\CachedGeoIpService;
use App\Services\GeoIp\CloudflareHeaderGeoIpService;
use App\Services\GeoIp\GeoIpService;
use App\Services\GeoIp\MaxMindGeoIpService;
use App\Services\GeoIp\NoOpGeoIpService;
use App\Services\Help\DatabaseHelpArticleSearch;
use App\Services\Help\HelpArticleSearch;
use App\Services\Notifications\HttpSlackNotifier;
use App\Services\Notifications\NoOpSlackNotifier;
use App\Services\Notifications\SlackNotifier;
use App\Services\SupportChat\AnthropicClaudeClient;
use App\Services\SupportChat\ClaudeClient;
use App\Services\SupportChat\SupportChatService;
use App\Services\SupportChat\TracedClaudeClient;
use App\Support\PermissionCatalog;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // There are deliberately no payment-gateway bindings here. Listora
        // takes no payment on the website and holds no merchant or processor
        // credentials, so there is no client to construct and no key to leak.

        // The production client, wrapped in the decorator that emits the LLM
        // span for each Messages API call. Arize ships no PHP auto-
        // instrumentor, so without this wrapper the call is invisible.
        // Unwrapping is a one-line change: tracing is additive and the inner
        // client knows nothing about it.
        $this->app->singleton(ClaudeClient::class, function ($app) {
            return new TracedClaudeClient(
                inner: new AnthropicClaudeClient(
                    apiKey: (string) (config('services.anthropic.api_key') ?? ''),
                ),
                tracing: $app->make(Tracing::class),
            );
        });

        $this->app->singleton(SupportChatService::class, function ($app) {
            return new SupportChatService($app->make(ClaudeClient::class));
        });

        // HelpArticleSearch — DB-backed by default. The interface is the swap
        // point for a real search engine when the article catalogue grows
        // beyond what a few LIKE queries comfortably serve.
        $this->app->singleton(HelpArticleSearch::class, function () {
            return new DatabaseHelpArticleSearch();
        });

        // SlackNotifier — wires HttpSlackNotifier when SLACK_OPS_WEBHOOK_URL is
        // set, NoOp otherwise. Singleton so jobs share the underlying Http
        // client instance across a worker's lifetime.
        $this->app->singleton(SlackNotifier::class, function ($app) {
            $url = (string) (config('services.slack.ops_webhook_url') ?? '');
            if ($url === '') {
                return new NoOpSlackNotifier();
            }

            return new HttpSlackNotifier($app->make(HttpFactory::class), $url);
        });

        // GeoIP precedence: MaxMind (richest data, when configured) →
        //                   Cloudflare headers (free, when behind CF) →
        //                   NoOp (tests, or when explicitly disabled).
        //
        // MaxMind is cached (lookups against a binary DB are expensive and
        // IP→geo is stable per IP). Cloudflare is NOT cached: its result
        // derives from per-request headers, and the cache key (the IP)
        // doesn't capture that — caching could pin an empty result from a
        // CLI/queue context for an hour.
        $this->app->singleton(GeoIpService::class, function ($app) {
            $cityPath = config('services.maxmind.mmdb_path');
            $anonPath = config('services.maxmind.anonymous_mmdb_path');

            if ($cityPath && is_readable($cityPath)) {
                return new CachedGeoIpService(
                    new MaxMindGeoIpService($cityPath, $anonPath),
                    $app->make(CacheRepository::class),
                );
            }

            // Read through config, never env() directly: `config:cache` runs on
            // every Cloud deploy and makes env() return null outside config/,
            // which would silently pin this to the Cloudflare branch forever.
            if (config('services.maxmind.disable_cloudflare', false)) {
                return new NoOpGeoIpService();
            }

            return new CloudflareHeaderGeoIpService();
        });
    }

    public function boot(): void
    {
        // TrackAuthEvents is picked up automatically by Laravel's event
        // auto-discovery (any class in app/Listeners/ with a subscribe()
        // method gets registered). Don't call Event::subscribe() here — that
        // causes the listener to fire twice for each auth event.

        $this->registerPermissionGates();
    }

    /**
     * Expose every catalog permission as a Gate ability so Blade can write
     * `@can('listings.edit')` and controllers `$this->authorize(...)` against
     * the same keys the `permission:` middleware uses. One Gate::before
     * handles the super-admin bypass for all of them.
     */
    private function registerPermissionGates(): void
    {
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);

        foreach (PermissionCatalog::keys() as $key) {
            Gate::define($key, fn (User $user) => $user->hasPermission($key));
        }
    }
}
