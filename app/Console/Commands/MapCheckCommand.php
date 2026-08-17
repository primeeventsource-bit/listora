<?php

namespace App\Console\Commands;

use App\Models\TrackingEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Explains why the visitor map on Admin -> Reports looks the way it does.
 *
 * The map has three distinct "there is no map" outcomes and they look similar
 * from the browser but have nothing to do with each other:
 *
 *   1. No geo-located events in the window  -> empty state, no map at all
 *   2. Events, but no Mapbox token          -> plotted grid, no basemap
 *   3. Token and events                     -> Mapbox
 *
 * On Laravel Cloud the usual cause is (1) on a freshly deployed environment,
 * or a token set under a name nothing reads. Guessing between them from a
 * screenshot wastes a deploy cycle, so this prints which one it is.
 */
class MapCheckCommand extends Command
{
    protected $signature = 'listora:map-check {--days=30 : Window to inspect}';

    protected $description = 'Diagnose what the Reports visitor map will render, and why';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $since = Carbon::now()->subDays($days);

        $token = config('services.mapbox.token');

        $this->line('');
        $this->line('<options=bold>Mapbox</>');
        $this->line('  config services.mapbox.token .... '.($token ? '<info>set</info> ('.$this->mask($token).')' : '<comment>not set</comment>'));

        foreach (['MAPBOX_ACCESS_TOKEN', 'MAPBOX_API_KEY', 'MAPBOX_TOKEN'] as $name) {
            $raw = env($name);
            $this->line(sprintf('  %-30s %s', $name, $raw ? '<info>'.$this->mask($raw).'</info>' : '<comment>unset</comment>'));
        }

        if ($token && ! str_starts_with($token, 'pk.')) {
            $this->line('');
            $this->warn('  The token does not start with "pk.". Mapbox GL needs a PUBLIC token;');
            $this->warn('  a secret (sk.) token is rejected by the browser and the map stays blank.');
        }

        $total = TrackingEvent::query()->where('occurred_at', '>=', $since)->count();

        $located = TrackingEvent::query()
            ->where('occurred_at', '>=', $since)
            ->get(['metadata'])
            ->filter(fn ($e) => filled(data_get($e->metadata, 'geo.latitude'))
                && filled(data_get($e->metadata, 'geo.longitude')))
            ->count();

        $this->line('');
        $this->line('<options=bold>Data (last '.$days.' days)</>');
        $this->line('  tracking events ................ '.number_format($total));
        $this->line('  with lat/lng in metadata.geo ... '.number_format($located));

        $this->line('');
        $this->line('<options=bold>Verdict</>');

        if ($located === 0) {
            $this->error('  No map: nothing in this window has coordinates.');
            $this->line('');

            if ($total === 0) {
                $this->line('  There are no events at all. On a non-production environment:');
                $this->line('      <info>php artisan db:seed --class=DemoTrafficSeeder</info>');
            } else {
                $this->line('  Events exist but carry no geo. GeoIpService resolves through');
                $this->line('  MaxMind, then Cloudflare headers, then a no-op that records');
                $this->line('  nothing — so an environment with neither configured writes');
                $this->line('  events without coordinates. Set MAXMIND_MMDB_PATH, or run');
                $this->line('  behind Cloudflare, or seed demo traffic on a dev environment.');
            }

            return self::SUCCESS;
        }

        if (! $token) {
            $this->warn('  Plotted grid, no basemap: '.number_format($located).' located events, no Mapbox token.');
            $this->line('');
            $this->line('  Every number on the page is correct and the pins are in the right');
            $this->line('  places — only the basemap is missing. Set MAPBOX_ACCESS_TOKEN to a');
            $this->line('  public pk.* token, then redeploy so config:cache picks it up.');

            return self::SUCCESS;
        }

        $this->info('  Mapbox map with '.number_format($located).' located events.');
        $this->line('');
        $this->line('  If the panel is still blank in the browser, it is client side:');
        $this->line('    - open the console and look for a 401 from api.mapbox.com');
        $this->line('      (token URL restrictions not covering this domain)');
        $this->line('    - confirm the token is a public pk.* one, not a secret sk.*');

        return self::SUCCESS;
    }

    /** Never print a whole credential to a terminal that may be logged. */
    private function mask(string $token): string
    {
        return mb_substr($token, 0, 8).'…'.mb_substr($token, -4);
    }
}
