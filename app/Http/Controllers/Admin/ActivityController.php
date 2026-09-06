<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdEventType;
use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\Inquiry;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The activity log: every recorded session, in full, for administrators.
 *
 * Three screens, answering three different questions.
 *
 *   index()    what happened, filtered - by address, account, listing,
 *              session, activity, device, place or date
 *   session()  what one visit did, in the order it did it
 *   visitor()  everything one visitor has ever done, across sessions
 *
 * The member-facing counterpart is MemberPerformance, which is deliberately
 * blind to addresses and to anyone else's traffic. This is not, and that is
 * the whole point of it: "is this traffic real, who actually visited, and
 * what did they do before they disputed the charge" cannot be answered from
 * approximate geography.
 *
 * Gated on activity.view, separately from reports.view, so seeing a visitor's
 * address is a decision somebody made rather than a side effect of granting
 * somebody reporting. Export is gated again on activity.export, because
 * taking the records out of the system is a different act from reading them:
 * an exported file is no longer covered by this application's retention
 * promise, and the privacy policy commits to 24 months.
 *
 * Nothing here writes. An audit trail an administrator can edit is not one.
 */
class ActivityController extends Controller
{
    private const PER_PAGE = 60;

    /** Hard ceiling on an export, so one filter cannot stream the table. */
    private const EXPORT_LIMIT = 25000;

    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $query = $this->filtered($request, $from, $to)
            ->with(['listing:id,slug,title,ad_number', 'actor:id,name,email', 'member:id,name,ad_number'])
            ->latest('occurred_at');

        $events = $query->clone()->paginate(self::PER_PAGE)->withQueryString();

        // Counted from the filtered set rather than the page, because the
        // question "how many sessions match this" is the reason to filter.
        $matching = $this->filtered($request, $from, $to);

        return view('admin.activity.index', [
            'events' => $events,
            'filters' => $this->filters($request, $from, $to),
            'types' => AdEventType::options(),
            'totals' => [
                'events' => $matching->clone()->count(),
                'sessions' => $matching->clone()->distinct()->count('session_id'),
                'visitors' => $matching->clone()->distinct()->count('visitor_id'),
                'accounts' => $matching->clone()->whereNotNull('actor_user_id')->distinct()->count('actor_user_id'),
            ],
            'points' => $this->mapPoints($matching->clone()),
            'mapboxToken' => config('services.mapbox.token'),
            'mapboxStyle' => config('services.mapbox.style'),
        ]);
    }

    /**
     * One session, oldest first.
     *
     * Ascending on purpose, against the convention everywhere else in the
     * console. A timeline read newest-first is the story backwards, and the
     * thing this screen exists to show - homepage, then a category, then a
     * listing, then send inquiry - only reads as a sequence in the order it
     * happened.
     */
    public function session(Request $request, string $session): View
    {
        $events = AdEvent::query()
            ->where('session_id', $session)
            ->with(['listing:id,slug,title,ad_number', 'actor:id,name,email'])
            ->orderBy('occurred_at')
            ->get();

        abort_if($events->isEmpty(), 404);

        $first = $events->first();
        $last = $events->last();

        return view('admin.activity.session', [
            'sessionId' => $session,
            'events' => $events,
            'first' => $first,
            'last' => $last,
            'duration' => $first->occurred_at?->diffForHumans($last->occurred_at, true) ?? '—',
            'account' => $this->accountFor($events),
        ]);
    }

    /**
     * One visitor, across every session they have had.
     *
     * The visitor id is a first-party opaque identifier, not an account. An
     * anonymous visitor gets one on arrival and keeps it; if they later sign
     * in, their account appears here beside the earlier activity, which is
     * the point - a dispute is usually about what somebody did before they
     * had an account.
     */
    public function visitor(Request $request, string $visitor): View
    {
        $events = AdEvent::query()
            ->where('visitor_id', $visitor)
            ->with(['listing:id,slug,title,ad_number'])
            ->orderByDesc('occurred_at')
            ->limit(500)
            ->get();

        abort_if($events->isEmpty(), 404);

        $account = $this->accountFor($events);

        $listingIds = $events->pluck('listing_id')->filter()->unique();

        return view('admin.activity.visitor', [
            'visitorId' => $visitor,
            'events' => $events,
            'account' => $account,
            'summary' => [
                'first_seen' => $events->min('occurred_at'),
                'last_seen' => $events->max('occurred_at'),
                'sessions' => $events->pluck('session_id')->filter()->unique()->count(),
                'page_views' => $events->whereIn('event_type', AdEventType::views())->count()
                    + $events->where('event_type', AdEventType::PageView)->count(),
                'events' => $events->count(),
                'addresses' => $events->pluck('ip_address')->filter()->unique()->values()->all(),
                'places' => $events->map(fn (AdEvent $e) => $this->place($e))->filter()->unique()->values()->all(),
                'devices' => $events->pluck('device_category')->filter()->unique()->values()->all(),
            ],
            'sessions' => $events
                ->filter(fn (AdEvent $e) => filled($e->session_id))
                ->groupBy('session_id')
                ->map(fn ($rows) => [
                    'started' => $rows->min('occurred_at'),
                    'ended' => $rows->max('occurred_at'),
                    'events' => $rows->count(),
                    'entry' => $rows->sortBy('occurred_at')->first()?->path,
                ])
                ->sortByDesc('started'),
            'listings' => $events
                ->filter(fn (AdEvent $e) => $e->listing_id !== null)
                ->groupBy('listing_id')
                ->map(fn ($rows) => ['listing' => $rows->first()->listing, 'views' => $rows->count()])
                ->sortByDesc('views'),
            'inquiries' => $account
                ? Inquiry::query()->where('user_id', $account->id)->latest()->limit(20)->get()
                : collect(),
            'offers' => $account
                ? Offer::query()->where('buyer_user_id', $account->id)->latest()->limit(20)->get()
                : collect(),
            'listingIds' => $listingIds,
        ]);
    }

    /**
     * The filtered log as CSV.
     *
     * Includes the address column. That is the reason for the separate
     * permission: this file leaves the application, and the retention promise
     * in the privacy policy travels no further than the database.
     */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);

        $query = $this->filtered($request, $from, $to)
            ->with(['listing:id,ad_number,title', 'actor:id,name,email'])
            ->orderBy('occurred_at')
            ->limit(self::EXPORT_LIMIT);

        $filename = 'listora-activity-'.$from->toDateString().'-to-'.$to->toDateString().'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            // `escape` passed explicitly: omitting it is deprecated as of PHP
            // 8.4, and an empty string is the correct value for a CSV anyone
            // else will read - backslash escaping is a PHP-ism that RFC 4180
            // does not have, and Excel and Sheets both mis-parse it.
            fputcsv($out, [
                'Occurred at (UTC)', 'Activity', 'Session', 'Visitor', 'Account', 'Email',
                'IP address', 'City', 'Region', 'Country', 'Device', 'Browser', 'OS',
                'Path', 'Referrer host', 'Source', 'Campaign', 'Listing', 'Ad number',
            ], escape: '');

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $e) {
                    fputcsv($out, [
                        $e->occurred_at?->toIso8601String(),
                        $e->event_type?->label() ?? $e->getRawOriginal('event_type'),
                        $e->session_id,
                        $e->visitor_id,
                        $e->actor?->name,
                        $e->actor?->email,
                        $e->ip_address,
                        $e->geo_city,
                        $e->geo_region,
                        $e->geo_country,
                        $e->device_category,
                        $e->browser,
                        $e->os,
                        $e->path,
                        $e->referrer_host,
                        $e->source_category,
                        $e->utm_campaign,
                        $e->listing?->title,
                        $e->listing_ref,
                    ], escape: '');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // -----------------------------------------------------------------
    // Filtering
    // -----------------------------------------------------------------

    private function filtered(Request $request, Carbon $from, Carbon $to): Builder
    {
        $query = AdEvent::query()->whereBetween('occurred_at', [$from, $to]);

        // Exact-match filters. Each one is the answer to "show me this
        // thing", so none of them is a LIKE - a partial session id matching
        // three sessions is not a useful answer to a question about one.
        foreach ([
            'ip' => 'ip_address',
            'session' => 'session_id',
            'visitor' => 'visitor_id',
            'type' => 'event_type',
            'device' => 'device_category',
            'country' => 'geo_country',
        ] as $param => $column) {
            if (filled($value = $request->query($param))) {
                $query->where($column, $value);
            }
        }

        if (filled($user = $request->query('user'))) {
            // Either side of an account: what they did signed in, and the
            // advertiser whose listings were being looked at.
            $query->where(fn (Builder $q) => $q
                ->where('actor_user_id', $user)
                ->orWhere('member_user_id', $user));
        }

        if (filled($listing = $request->query('listing'))) {
            $query->where(fn (Builder $q) => $q
                ->where('listing_id', $listing)
                ->orWhere('listing_ref', $listing));
        }

        if (filled($city = $request->query('city'))) {
            $query->where('geo_city', 'like', $city.'%');
        }

        // The free-text box searches the things somebody types into it from a
        // support ticket: an address, a path, a campaign, an ad number.
        if (filled($q = $request->query('q'))) {
            $like = '%'.$q.'%';

            $query->where(fn (Builder $w) => $w
                ->where('ip_address', 'like', $like)
                ->orWhere('path', 'like', $like)
                ->orWhere('ad_number', 'like', $like)
                ->orWhere('listing_ref', 'like', $like)
                ->orWhere('utm_campaign', 'like', $like)
                ->orWhere('referrer_host', 'like', $like)
                ->orWhere('geo_city', 'like', $like));
        }

        return $query;
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function range(Request $request): array
    {
        $to = $this->date($request->query('to')) ?? now();
        $from = $this->date($request->query('from')) ?? $to->copy()->subDays(30);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function date(?string $value): ?Carbon
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

    private function filters(Request $request, Carbon $from, Carbon $to): array
    {
        return [
            'q' => $request->query('q'),
            'ip' => $request->query('ip'),
            'user' => $request->query('user'),
            'listing' => $request->query('listing'),
            'session' => $request->query('session'),
            'visitor' => $request->query('visitor'),
            'type' => $request->query('type'),
            'device' => $request->query('device'),
            'city' => $request->query('city'),
            'country' => $request->query('country'),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * The account behind a set of events, if any of them were signed in.
     *
     * Taken from the events rather than looked up, because the question is
     * "did this visitor identify themselves at any point", and the answer is
     * in the rows.
     */
    private function accountFor($events): ?User
    {
        $id = $events->pluck('actor_user_id')->filter()->first();

        return $id ? User::find($id) : null;
    }

    private function place(AdEvent $event): ?string
    {
        $label = trim(implode(', ', array_filter([
            $event->geo_city,
            $event->geo_region,
            $event->geo_country,
        ])));

        return $label !== '' ? $label : null;
    }

    /**
     * Points for the live traffic map, clustered by rounded coordinate.
     *
     * Rounded to two decimals here rather than the three the member map uses
     * - this map covers every event on the site rather than one advertiser's,
     * and at that volume a tighter cluster is a thousand markers on one city.
     */
    private function mapPoints(Builder $query): array
    {
        return $query
            ->whereNotNull('geo_lat')
            ->whereNotNull('geo_lng')
            ->limit(5000)
            ->get(['geo_lat', 'geo_lng', 'geo_city', 'geo_region', 'geo_country'])
            ->groupBy(fn ($e) => round((float) $e->geo_lat, 2).','.round((float) $e->geo_lng, 2))
            ->map(function ($group, string $key) {
                [$lat, $lng] = array_map('floatval', explode(',', $key));

                return [
                    'lat' => $lat,
                    'lng' => $lng,
                    'events' => $group->count(),
                    'label' => $this->place($group->first()) ?: 'Unknown',
                ];
            })
            ->sortByDesc('events')
            ->values()
            ->all();
    }
}
