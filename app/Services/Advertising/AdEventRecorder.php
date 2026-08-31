<?php

namespace App\Services\Advertising;

use App\Enums\AdEventType;
use App\Models\AdEvent;
use App\Models\Listing;
use App\Services\GeoIp\GeoIpService;
use App\Support\UserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records advertising events.
 *
 * The point of this table is evidence that a member's advertisement ran and
 * was reachable, so recording must never be able to take the advertisement
 * down. Every path here is wrapped: a failed geo lookup, an unexpectedly long
 * referrer, a database hiccup - none of them may turn a visitor's page view
 * into a 500. A missing row is a gap in a report; a thrown exception is an
 * outage on the thing being reported on.
 *
 * Geography is approximate by construction. It is derived from IP, which
 * places a visitor near their network's registered location and is routinely
 * a suburb or a city out. Nothing downstream may present it as where somebody
 * physically is.
 */
class AdEventRecorder
{
    public function __construct(private readonly GeoIpService $geo)
    {
    }

    /**
     * Record one event against a listing.
     *
     * Returns the row, or null when nothing was written - which is not an
     * error and callers should not treat it as one.
     */
    public function record(Request $request, AdEventType $type, ?Listing $listing = null): ?AdEvent
    {
        try {
            return $this->write($request, $type, $listing);
        } catch (Throwable $e) {
            // Deliberately swallowed. See the class docblock: the advertisement
            // outranks the record of it. Logged so a silent gap is still
            // discoverable rather than merely silent.
            //
            // The reason goes in the message, not only in the context. The
            // first failure here reached the log as "Ad event not recorded"
            // with every context field rendered null, which said that
            // something broke and nothing about what - and the recorder's own
            // design means there is no other symptom to work from.
            Log::warning(sprintf(
                'Ad event not recorded [%s, listing %s]: %s: %s',
                $type->value,
                $listing?->id ?? '-',
                $e::class,
                $e->getMessage(),
            ));

            return null;
        }
    }

    private function write(Request $request, AdEventType $type, ?Listing $listing): AdEvent
    {
        $ip = $request->ip();
        $ua = (string) $request->userAgent();
        $agent = UserAgent::parse($ua);
        $geo = $this->geo->lookup($ip);

        $referrer = $request->headers->get('referer');
        $owner = $listing?->owner;

        return AdEvent::create([
            'ad_number' => $owner?->ad_number,
            'listing_ref' => $listing?->ad_number,
            'member_user_id' => $listing?->owner_id,
            'listing_id' => $listing?->id,

            'event_type' => $type->value,
            'url' => Str::limit($request->fullUrl(), 500, ''),
            'path' => Str::limit($request->path(), 250, ''),

            'referrer' => $referrer ? Str::limit($referrer, 500, '') : null,
            'referrer_host' => $referrer ? Str::limit((string) parse_url($referrer, PHP_URL_HOST), 250, '') : null,

            'utm_source' => $this->param($request, 'utm_source', 128),
            'utm_medium' => $this->param($request, 'utm_medium', 128),
            'utm_campaign' => $this->param($request, 'utm_campaign', 191),
            'utm_term' => $this->param($request, 'utm_term', 191),
            'utm_content' => $this->param($request, 'utm_content', 191),
            'click_id' => $this->clickId($request),
            'source_category' => AdTrafficSource::classify($request, $referrer),

            // The first-party visitor cookie already set by
            // CaptureLandingAttribution, so paid-click attribution and
            // advertising analytics describe the same person.
            'visitor_id' => $request->cookie('lst_vid')
                ?: $request->attributes->get('listora_visitor_id'),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'actor_user_id' => $request->user()?->id,

            // ADMIN ONLY. AdEvent::scopeForMember() never selects this.
            'ip_address' => $ip,
            'ip_hash' => $ip ? hash('sha256', $ip.'|'.config('app.key')) : null,

            // Bounded to their columns. These are third-party strings from a
            // GeoIP database, so their length is not ours to assume - and an
            // oversized value is rejected outright by MySQL in strict mode
            // while passing silently on the SQLite the tests run against.
            'geo_city' => $this->clip($geo->city, 128),
            'geo_region' => $this->clip($geo->region, 128),
            'geo_country' => $this->clip($geo->country, 2),
            'geo_lat' => $geo->latitude,
            'geo_lng' => $geo->longitude,

            'device_category' => $agent['device_category'],
            'browser' => $agent['browser'],
            'os' => $agent['os'],
            'user_agent' => Str::limit($ua, 500, ''),

            'occurred_at' => now(),
        ]);
    }

    /** Bound a value to its column, or null if there is nothing to store. */
    private function clip(?string $value, int $max): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private function param(Request $request, string $key, int $max): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? Str::limit($value, $max, '') : null;
    }

    /**
     * The advertising network's own click identifier, whichever sent it.
     *
     * Kept as one column rather than one per network: reports ask "did this
     * visit come from a paid click", not "which vendor's parameter names it".
     */
    private function clickId(Request $request): ?string
    {
        foreach (['gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'ttclid'] as $key) {
            $value = $request->query($key);

            if (is_string($value) && $value !== '') {
                return Str::limit($value, 191, '');
            }
        }

        return null;
    }
}
