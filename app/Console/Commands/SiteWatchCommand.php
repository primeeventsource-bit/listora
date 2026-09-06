<?php

namespace App\Console\Commands;

use App\Services\Notifications\SlackNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Watches the public domain from outside the application.
 *
 * The custom hostname in front of this site has failed five times in six days.
 * Each failure looks identical: DNS stays correct, the application stays
 * healthy on its vanity URL, and the certificate for listora1.com simply
 * stops working. Recovery requires re-registering the domain, which nobody
 * can do until somebody notices.
 *
 * The last occurrence ran for 58 hours because nothing was watching. This does
 * not fix the fault - it cannot, the fault is in the platform's Cloudflare
 * integration - but it turns "down until a human happens to look" into "down
 * until the next check".
 *
 * Checks APP_URL rather than the vanity host on purpose: the vanity host has
 * stayed up through every single outage, so watching it would have reported
 * green throughout all five.
 */
class SiteWatchCommand extends Command
{
    protected $signature = 'listora:site-watch
        {--force-alert : Send the alert even if one was already sent for this outage}';

    protected $description = 'Check that the public site is reachable, and raise an alert when it is not';

    /** Suppresses repeat alerts while one outage is ongoing. */
    private const ALERTED_KEY = 'site-watch:alerted';

    private const RECOVERED_KEY = 'site-watch:down-since';

    public function handle(SlackNotifier $slack): int
    {
        $url = rtrim((string) config('app.url'), '/').'/up';

        [$ok, $detail] = $this->probe($url);

        if ($ok) {
            $this->recovered($slack, $url);

            $this->info("OK — {$url}");

            return self::SUCCESS;
        }

        $this->error("DOWN — {$url}: {$detail}");

        // Logged every time, so the log shows the shape of an outage even when
        // no alert channel is configured.
        Log::error("Site watch: public site unreachable [{$url}]: {$detail}");

        $this->raiseAlert($slack, $url, $detail);

        // Non-zero so a scheduler or uptime wrapper can see the failure too.
        return self::FAILURE;
    }

    /** @return array{0:bool,1:string} */
    private function probe(string $url): array
    {
        try {
            $response = Http::timeout(15)->withoutRedirecting()->get($url);

            return $response->successful()
                ? [true, (string) $response->status()]
                : [false, 'HTTP '.$response->status()];
        } catch (Throwable $e) {
            // A TLS failure lands here, which is exactly how every one of these
            // outages has presented.
            return [false, $e::class.': '.$e->getMessage()];
        }
    }

    private function raiseAlert(SlackNotifier $slack, string $url, string $detail): void
    {
        $downSince = Cache::get(self::RECOVERED_KEY);

        if (! $downSince) {
            Cache::put(self::RECOVERED_KEY, $downSince = now()->toDateTimeString(), now()->addDays(7));
        }

        // One alert per outage, not one per check. A message every ten minutes
        // for two days is noise that gets muted, and a muted channel is worse
        // than no channel.
        if (Cache::get(self::ALERTED_KEY) && ! $this->option('force-alert')) {
            $this->line('  (alert already sent for this outage)');

            return;
        }

        Cache::put(self::ALERTED_KEY, true, now()->addDays(7));

        $slack->send([
            'text' => "🔴 *Listora is unreachable*\n"
                ."`{$url}` — {$detail}\n"
                ."Down since {$downSince}.\n\n"
                .'The application itself is probably fine — check the vanity URL. '
                .'This has been the Cloudflare custom hostname every previous time: '
                .'re-register the domain in Laravel Cloud, then update the '
                .'`_cf-custom-hostname` TXT record, which rotates on every re-registration.',
        ]);
    }

    private function recovered(SlackNotifier $slack, string $url): void
    {
        if (! Cache::get(self::ALERTED_KEY)) {
            return;
        }

        $downSince = Cache::get(self::RECOVERED_KEY);

        Cache::forget(self::ALERTED_KEY);
        Cache::forget(self::RECOVERED_KEY);

        // Recovery is worth saying out loud: an alert with no all-clear leaves
        // people checking manually to find out whether it is still broken.
        $slack->send([
            'text' => "🟢 *Listora is reachable again*\n`{$url}` is answering."
                .($downSince ? " Was down from {$downSince}." : ''),
        ]);
    }
}
