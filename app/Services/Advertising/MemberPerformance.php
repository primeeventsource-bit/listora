<?php

namespace App\Services\Advertising;

use App\Enums\AdEventType;
use App\Models\AdEvent;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What one advertiser's advertising is doing.
 *
 * Extracted from Owner\PerformanceController when performance moved onto the
 * dashboard. It is a service rather than a controller method because these
 * numbers are the product - an advertiser paying up to $3,995 is buying
 * exactly this - and they should be computed in one place that can be read
 * and tested on its own.
 *
 * Every query goes through AdEvent::forMember(), which scopes to the viewer's
 * own listings and drops ip_address at the SQL level. That is not belt and
 * braces: this data includes visitor geography, and the difference between
 * showing an advertiser "Orlando, Florida" and showing them a visitor's IP
 * address is the difference between analytics and surveillance. Keeping the
 * restriction in the scope rather than in each query means a panel added here
 * later inherits it.
 *
 * Location is approximate everywhere it appears. It is derived from IP and
 * lands near the visitor's network, not the visitor.
 */
class MemberPerformance
{
    /** Ranges offered in the filter, in days. Custom is handled separately. */
    public const RANGES = [
        'today' => 0,
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    /**
     * @return array<string, mixed> everything the dashboard's performance
     *                              sections render
     */
    public function forRequest(Request $request, int $userId): array
    {
        [$from, $to, $rangeKey] = $this->resolveRange($request);

        $listings = Listing::query()
            ->ownedBy($userId)
            ->orderBy('title')
            // slug is not decoration: Listing::getRouteKeyName() returns it,
            // so route('owner.listings.edit', $listing) cannot build a URL
            // without it and throws while rendering.
            ->get(['id', 'slug', 'ad_number', 'title', 'status']);

        // A listing filter is only honored when the listing is actually
        // theirs. Without the check, a guessed id would report someone else's
        // traffic through this advertiser's own screen.
        $listingId = $request->integer('listing') ?: null;
        $selected = $listingId ? $listings->firstWhere('id', $listingId) : null;
        $listingId = $selected?->id;

        $events = AdEvent::query()
            ->forMember($userId)
            ->between($from, $to)
            ->when($listingId, fn ($q) => $q->where('listing_id', $listingId))
            ->get();

        $views = $events->whereIn('event_type', AdEventType::views());

        return [
            'perfListings' => $listings,
            'selectedListing' => $selected,
            'rangeKey' => $rangeKey,
            'rangeLabel' => $this->rangeLabel($rangeKey, $from, $to),
            'from' => $from,
            'to' => $to,

            'totals' => [
                'views' => $views->count(),
                // Distinct visitors, not distinct sessions: one person
                // returning three times is one visitor and three views, and
                // conflating them overstates reach.
                'visitors' => $views->pluck('visitor_id')->filter()->unique()->count(),
                'inquiries' => $this->countInquiries($userId, $from, $to, $listingId),
                'offers' => $this->countOffers($userId, $from, $to, $listingId),
            ],

            'funnel' => $this->funnel($events),
            'points' => $this->mapPoints($events),
            'places' => $this->places($events),
            'sources' => $this->sources($views),
            'devices' => $this->devices($views),
            'perListing' => $this->perListing($views, $listings),

            'mapboxToken' => config('services.mapbox.token'),
            'mapboxStyle' => config('services.mapbox.style'),
        ];
    }

    /** @return array{0:Carbon,1:Carbon,2:string} */
    private function resolveRange(Request $request): array
    {
        $key = (string) $request->query('range', '30d');

        if ($key === 'custom') {
            $from = $this->parseDate($request->query('from')) ?? now()->subDays(30);
            $to = $this->parseDate($request->query('to')) ?? now();

            // A backwards range returns nothing and looks like missing data
            // rather than a mistyped filter, so it is straightened out.
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            return [$from->startOfDay(), $to->endOfDay(), 'custom'];
        }

        $days = self::RANGES[$key] ?? 30;
        $key = array_key_exists($key, self::RANGES) ? $key : '30d';

        return [now()->subDays($days)->startOfDay(), now()->endOfDay(), $key];
    }

    /** Said in words, because the numbers beside it mean nothing without it. */
    private function rangeLabel(string $key, Carbon $from, Carbon $to): string
    {
        return match ($key) {
            'today' => 'today',
            '7d' => 'in the last 7 days',
            '90d' => 'in the last 90 days',
            'custom' => 'from '.$from->format('j M Y').' to '.$to->format('j M Y'),
            default => 'in the last 30 days',
        };
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function countInquiries(int $userId, Carbon $from, Carbon $to, ?int $listingId): int
    {
        return Inquiry::query()
            ->forListingsOwnedBy($userId)
            ->when($listingId, fn ($q) => $q->where('listing_id', $listingId))
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function countOffers(int $userId, Carbon $from, Carbon $to, ?int $listingId): int
    {
        return Offer::query()
            ->forListingsOwnedBy($userId)
            ->when($listingId, fn ($q) => $q->where('listing_id', $listingId))
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * Points for the map, clustered by rounded coordinate.
     *
     * Rounded to three decimals - roughly 100 metres - so repeat visits from
     * one network stack into a single marker with a count rather than drawing
     * a hundred markers on the same pixel.
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
                    'label' => $this->placeLabel($first) ?: 'Unknown',
                ];
            })
            ->sortByDesc('events')
            ->values()
            ->all();
    }

    /**
     * The engagement funnel, as counts per step.
     *
     * Steps with no events are kept rather than dropped. A funnel that hides
     * its empty stages reads as though those stages do not exist - and "nobody
     * got as far as an offer" is the single most useful thing this panel can
     * tell an advertiser.
     *
     * @return array<int, array{label:string, count:int}>
     */
    private function funnel(Collection $events): array
    {
        $counts = $events->groupBy(fn ($e) => $e->event_type?->value)->map->count();

        $steps = [
            'Advertisement viewed' => [AdEventType::AdView, AdEventType::ListingView],
            'Inquiry or offer started' => [AdEventType::InquiryStarted, AdEventType::OfferStarted],
            'Inquiry submitted' => [AdEventType::InquirySubmitted],
            'Offer submitted' => [AdEventType::OfferSubmitted],
            'Conversation started' => [AdEventType::MessageStarted],
        ];

        $out = [];

        foreach ($steps as $label => $types) {
            $out[] = [
                'label' => $label,
                'count' => collect($types)->sum(fn (AdEventType $t) => (int) $counts->get($t->value, 0)),
            ];
        }

        return $out;
    }

    /** Top places as a list, which is what the map is actually saying. */
    private function places(Collection $events): array
    {
        return $events
            ->filter(fn ($e) => filled($e->geo_city) || filled($e->geo_country))
            ->groupBy(fn ($e) => $this->placeLabel($e))
            ->map->count()
            ->sortDesc()
            ->take(8)
            ->all();
    }

    private function placeLabel(AdEvent $event): string
    {
        return trim(implode(', ', array_filter([
            $event->geo_city,
            $event->geo_region,
            $event->geo_country,
        ])));
    }

    private function sources(Collection $views): array
    {
        return $views
            ->groupBy('source_category')
            ->map->count()
            ->sortDesc()
            ->mapWithKeys(fn ($count, $key) => [AdTrafficSource::label((string) $key) => $count])
            ->all();
    }

    /**
     * The device breakdown an advertiser sees.
     *
     * Bot traffic is counted as desktop rather than listed under its own
     * heading. The rows are kept either way - nothing is dropped, and this
     * still totals to the view count above it - so the change is what the
     * category is called, not what is counted.
     *
     * Only the member-facing view is folded. Admin reporting reads ad_events
     * directly and still sees `bot` for what it is, which is where the
     * question "how much of this traffic is crawlers" has to stay answerable.
     */
    private function devices(Collection $views): array
    {
        return $views
            ->groupBy(fn ($view) => $view->device_category === 'bot' ? 'desktop' : $view->device_category)
            ->map->count()
            ->sortDesc()
            ->all();
    }

    /** @return array<int, array{listing:Listing, views:int, visitors:int}> */
    private function perListing(Collection $views, Collection $listings): array
    {
        $byListing = $views->groupBy('listing_id');

        return $listings
            ->map(fn (Listing $listing) => [
                'listing' => $listing,
                'views' => $byListing->get($listing->id, collect())->count(),
                'visitors' => $byListing->get($listing->id, collect())
                    ->pluck('visitor_id')->filter()->unique()->count(),
            ])
            ->sortByDesc('views')
            ->values()
            ->all();
    }
}
