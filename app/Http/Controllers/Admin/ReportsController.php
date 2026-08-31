<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\PpcVisitor;
use App\Models\TrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Traffic, geography, and campaign attribution.
 *
 * `reports.view` and `reports.export` have gated a module that did not exist
 * since the permission catalog shipped, while TrackingService wrote a
 * geo-enriched row for every recorded event and CaptureLandingAttribution
 * wrote a first-touch row for every paid click. Both were being collected and
 * neither could be read.
 *
 * Everything here is derived at request time from `tracking_events`, which is
 * append-only and hash-chained — there is no rollup table to drift out of step
 * with it, and no write path in this controller at all.
 *
 * Geo lives in `metadata.geo`, written by TrackingService from GeoIpService.
 * It is JSON rather than columns, so the aggregation happens in PHP over a
 * bounded window rather than in SQL. That is a deliberate ceiling: see
 * MAX_ROWS, and the note on it.
 */
class ReportsController extends Controller
{
    /**
     * Hard ceiling on rows pulled into memory for geo aggregation.
     *
     * `metadata` is JSON, so grouping by country cannot be pushed into the
     * database portably. Rather than let this screen degrade silently as
     * traffic grows, it takes the most recent N events in the window and says
     * so on the page when it truncates — a number that quietly stops being the
     * whole picture is worse than one that admits its bound.
     */
    private const MAX_ROWS = 20000;

    private const WINDOWS = [7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'];

    public function index(Request $request): View
    {
        $days = (int) $request->query('days', 30);
        $days = array_key_exists($days, self::WINDOWS) ? $days : 30;

        $since = Carbon::now()->subDays($days)->startOfDay();

        $events = $this->eventsSince($since);

        /*
         | Geography comes from ad_events, not tracking_events.
         |
         | tracking_events only ever records a visit that arrived carrying
         | attribution parameters - a paid click - so on a site not yet running
         | campaigns it is empty, and the map was empty with it. ad_events
         | records every visit to an advertising or listing page, which is what
         | this panel is asking about.
         |
         | The other panels still read tracking_events: they are about
         | attribution and surfaces, which is what that table is for.
         */
        $adEvents = $this->adEventsSince($since);

        return view('admin.reports.index', [
            'days' => $days,
            'windows' => self::WINDOWS,
            'truncated' => $events->count() >= self::MAX_ROWS,
            'totals' => $this->totals($events, $adEvents, $since),
            'daily' => $this->daily($events, $since, $days),
            'byType' => $this->byType($events),
            'bySurface' => $this->bySurface($events),
            'countries' => $this->countries($adEvents),
            'cities' => $this->cities($adEvents),
            'points' => $this->mapPoints($adEvents),
            'anonymised' => $this->anonymised($events),
            'campaigns' => $this->campaigns($since),
            'mapboxToken' => config('services.mapbox.token'),
            'mapboxStyle' => config('services.mapbox.style'),
        ]);
    }

    /**
     * CSV of the geo breakdown. Streamed rather than built in memory, because
     * an export is exactly the request most likely to be run against the
     * widest window on the busiest day.
     */
    public function export(Request $request): StreamedResponse
    {
        $days = (int) $request->query('days', 30);
        $days = array_key_exists($days, self::WINDOWS) ? $days : 30;

        $rows = $this->countries($this->adEventsSince(Carbon::now()->subDays($days)->startOfDay()));

        $filename = 'listora-traffic-by-country-'.Carbon::now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            // `escape` passed explicitly: omitting it is deprecated as of PHP
            // 8.4 and would put a notice in the logs on every export. Empty
            // string is also the correct value for a CSV anyone else will read
            // — backslash escaping is a PHP-ism that RFC 4180 does not have,
            // and Excel and Sheets both mis-parse it.
            fputcsv($out, ['Country', 'Events', 'Visitors', 'Share %'], escape: '');

            foreach ($rows as $row) {
                fputcsv($out, [$row['country'], $row['events'], $row['visitors'], $row['share']], escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ------------------------------------------------------------- aggregation

    private function eventsSince(Carbon $since): Collection
    {
        return TrackingEvent::query()
            ->where('occurred_at', '>=', $since)
            ->latest('occurred_at')
            ->limit(self::MAX_ROWS)
            ->get(['id', 'event_type', 'visitor_id', 'surface', 'metadata', 'occurred_at']);
    }

    /**
     * Advertising visits in the window, for the geography panels.
     *
     * ad_events stores geography in columns rather than JSON, so this could be
     * aggregated in SQL. It is not, yet, because the panels below are shared
     * with the tracking_events shape and the same MAX_ROWS ceiling applies
     * honestly to both - the page still says when it truncates.
     */
    private function adEventsSince(Carbon $since): Collection
    {
        return AdEvent::query()
            ->where('occurred_at', '>=', $since)
            ->latest('occurred_at')
            ->limit(self::MAX_ROWS)
            ->get(['id', 'visitor_id', 'geo_city', 'geo_region', 'geo_country', 'geo_lat', 'geo_lng', 'occurred_at']);
    }

    private function totals(Collection $events, Collection $adEvents, Carbon $since): array
    {
        return [
            'events' => $events->count(),
            'visitors' => $events->pluck('visitor_id')->filter()->unique()->count(),
            // Counted from the same rows the map and the country table are
            // drawn from. Reading it off tracking_events instead would report
            // zero countries beside a map showing pins, which reads as a bug
            // in the map.
            'countries' => $adEvents->pluck('geo_country')->filter()->unique()->count(),
            'attributed' => PpcVisitor::query()->where('first_seen_at', '>=', $since)->count(),
        ];
    }

    /** @return list<array{date: string, label: string, events: int}> */
    private function daily(Collection $events, Carbon $since, int $days): array
    {
        $counts = $events->groupBy(fn ($e) => $e->occurred_at->toDateString())->map->count();

        $out = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $key = $date->toDateString();

            $out[] = [
                'date' => $key,
                'label' => $date->format('j M'),
                'events' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $out;
    }

    private function byType(Collection $events): array
    {
        return $events->groupBy('event_type')
            ->map->count()
            ->sortDesc()
            ->all();
    }

    private function bySurface(Collection $events): array
    {
        return $events->groupBy(fn ($e) => $e->surface?->value ?? 'unknown')
            ->map->count()
            ->sortDesc()
            ->all();
    }

    /** @return list<array{country: string, events: int, visitors: int, share: float}> */
    private function countries(Collection $events): array
    {
        $total = max(1, $events->count());

        return $events
            ->groupBy(fn ($e) => $e->geo_country ?: 'Unknown')
            ->map(fn (Collection $group, string $country) => [
                'country' => $country,
                'events' => $group->count(),
                'visitors' => $group->pluck('visitor_id')->filter()->unique()->count(),
                'share' => round($group->count() / $total * 100, 1),
            ])
            ->sortByDesc('events')
            ->values()
            ->all();
    }

    /** @return list<array{city: string, country: string, events: int}> */
    private function cities(Collection $events): array
    {
        return $events
            ->filter(fn ($e) => filled($e->geo_city))
            ->groupBy(fn ($e) => $e->geo_city.'|'.$e->geo_country)
            ->map(function (Collection $group, string $key) {
                [$city, $country] = explode('|', $key);

                return ['city' => $city, 'country' => $country, 'events' => $group->count()];
            })
            ->sortByDesc('events')
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * One feature per distinct coordinate, weighted by event count.
     *
     * Rounded to three decimals (~110m) before grouping: the seeder and any
     * real city-level provider jitter coordinates slightly, and without
     * rounding a busy city becomes hundreds of one-event pins stacked on top
     * of each other, which the map cannot usefully size.
     *
     * @return list<array{lat: float, lng: float, events: int, label: string}>
     */
    private function mapPoints(Collection $events): array
    {
        return $events
            ->filter(fn ($e) => filled($e->geo_lat) && filled($e->geo_lng))
            ->groupBy(fn ($e) => round((float) $e->geo_lat, 3).','.round((float) $e->geo_lng, 3))
            ->map(function (Collection $group, string $key) {
                [$lat, $lng] = array_map('floatval', explode(',', $key));
                $first = $group->first();

                return [
                    'lat' => $lat,
                    'lng' => $lng,
                    'events' => $group->count(),
                    'label' => trim(implode(', ', array_filter([
                        $first->geo_city,
                        $first->geo_region,
                        $first->geo_country,
                    ]))) ?: 'Unknown',
                ];
            })
            ->sortByDesc('events')
            ->values()
            ->all();
    }

    /**
     * VPN, Tor, and datacenter traffic.
     *
     * Surfaced rather than filtered out. These are legitimate visitors far
     * more often than not, but a geo panel that silently drops them reports a
     * map of where people appear to be with no note that some of them are not
     * there.
     */
    private function anonymised(Collection $events): array
    {
        return [
            'vpn' => $events->filter(fn ($e) => (bool) data_get($e->metadata, 'geo.is_vpn'))->count(),
            'tor' => $events->filter(fn ($e) => (bool) data_get($e->metadata, 'geo.is_tor'))->count(),
            'datacenter' => $events->filter(fn ($e) => (bool) data_get($e->metadata, 'geo.is_datacenter'))->count(),
        ];
    }

    /** First-touch paid attribution, straight from ppc_visitors. */
    private function campaigns(Carbon $since): array
    {
        return PpcVisitor::query()
            ->where('first_seen_at', '>=', $since)
            ->selectRaw('utm_source, utm_medium, utm_campaign, COUNT(*) as visitors')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('visitors')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'source' => $row->utm_source ?: 'direct',
                'medium' => $row->utm_medium ?: '—',
                'campaign' => $row->utm_campaign ?: '—',
                'visitors' => (int) $row->visitors,
            ])
            ->all();
    }
}
