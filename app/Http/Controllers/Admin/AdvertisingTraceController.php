<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdEvent;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Advertising traffic, in full, for administrators.
 *
 * The member-facing counterpart is Owner\PerformanceController, which is
 * deliberately blind to IP addresses. This one is not: it exists to answer
 * "who actually visited this advertisement, from where, and is this traffic
 * real" - a fraud and dispute question that cannot be answered from
 * approximate geography alone.
 *
 * Gated on advertising.trace rather than reports.view, so seeing a visitor's
 * address is a decision somebody made rather than a side effect of granting
 * somebody reporting.
 *
 * The privacy policy commits to keeping these records 24 months. Nothing here
 * writes, so nothing here can extend that - but a feature that exported them
 * somewhere else would, which is worth remembering before adding one.
 */
class AdvertisingTraceController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        $query = AdEvent::query()
            ->with(['listing:id,slug,title,ad_number', 'member:id,name,email,ad_number'])
            ->whereBetween('occurred_at', [$from, $to])
            ->latest('occurred_at');

        $this->applySearch($query, $request);

        $events = $query->paginate(self::PER_PAGE)->withQueryString();

        return view('admin.advertising.index', [
            'events' => $events,
            'filters' => [
                'q' => $request->query('q'),
                'ip' => $request->query('ip'),
                'region' => $request->query('region'),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'totals' => [
                'events' => (clone $query)->toBase()->getCountForPagination(),
                'visitors' => (clone $query)->distinct()->count('visitor_id'),
            ],
        ]);
    }

    /**
     * One advertiser's traffic, newest first.
     *
     * The chronological log the brief describes: what happened, when, roughly
     * where from, by what route, on what device.
     */
    public function member(Request $request, User $user): View
    {
        [$from, $to] = $this->range($request);

        $events = AdEvent::query()
            ->with('listing:id,slug,title,ad_number')
            ->where('member_user_id', $user->id)
            ->whereBetween('occurred_at', [$from, $to])
            ->latest('occurred_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.advertising.member', [
            'member' => $user,
            'events' => $events,
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
        ]);
    }

    /**
     * One search box across the identifiers, plus two specific ones.
     *
     * An investigator has a number and rarely knows what kind it is - a
     * member number, a listing number, a listing id from a report. Asking them
     * to pick the right field first is asking them to already know the answer,
     * so `q` tries them all.
     *
     * IP and region are separate because they are the two searches where a
     * partial match is the point: "everything from this address" and
     * "everything from this region".
     */
    private function applySearch($query, Request $request): void
    {
        if ($term = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($term) {
                $q->where('ad_number', $term)
                    ->orWhere('listing_ref', $term)
                    ->orWhere('listing_id', is_numeric($term) ? (int) $term : 0)
                    ->orWhere('url', 'like', '%'.$term.'%')
                    ->orWhereHas('member', function ($m) use ($term) {
                        $m->where('name', 'like', '%'.$term.'%')
                            ->orWhere('email', 'like', '%'.$term.'%');
                    });
            });
        }

        if ($ip = trim((string) $request->query('ip'))) {
            // Prefix match, so a /24 can be swept by entering "203.0.113."
            $query->where('ip_address', 'like', $ip.'%');
        }

        if ($region = trim((string) $request->query('region'))) {
            $query->where(function ($q) use ($region) {
                $q->where('geo_region', 'like', '%'.$region.'%')
                    ->orWhere('geo_city', 'like', '%'.$region.'%')
                    ->orWhere('geo_country', $region);
            });
        }
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function range(Request $request): array
    {
        $from = $this->parse($request->query('from')) ?? now()->subDays(30);
        $to = $this->parse($request->query('to')) ?? now();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    private function parse(?string $value): ?Carbon
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
}
