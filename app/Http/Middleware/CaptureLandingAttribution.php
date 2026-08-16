<?php

namespace App\Http\Middleware;

use App\Services\Tracking\TrackingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Catches paid-click attribution on the page the click actually lands on.
 *
 * `ppc_visitors` and TrackingService::captureFirstTouch already existed, but
 * nothing on the web stack reached them: the only callers were the JSON
 * tracking endpoint and the listing-wizard submit. So a Google Ads click
 * arriving at /browse carried a `gclid`, rendered a page, and dropped it —
 * and an inquiry sent twenty minutes later had no campaign attached to it.
 * Ads reports the click, the database reports the inquiry, and nothing joins
 * the two.
 *
 * Runs before the response so the visitor id is available to the view, which
 * needs it to stamp the same id onto the gtag payload.
 *
 * ---------------------------------------------------------------------------
 * Cookie policy, deliberately narrow
 *
 * The `lst_vid` cookie is set ONLY for a visitor who arrives with attribution
 * parameters, or who already carries one. Organic and direct traffic leave
 * with no cookie at all, because there is nothing to attribute them to and
 * cookie-ing someone to learn nothing is not a trade worth making.
 *
 * That narrowness is also what keeps this defensible under consent rules: the
 * cookie is first-party, is written in response to the visitor's own campaign
 * click, holds an opaque id and no personal data, and never reaches a third
 * party from here. Anything broader belongs behind a consent banner, which
 * this app does not yet have.
 */
class CaptureLandingAttribution
{
    /** Two years, matching the Google Ads click-attribution window ceiling. */
    private const COOKIE_MINUTES = 60 * 24 * 730;

    /** Any one of these means the visit came from somewhere worth recording. */
    private const ATTRIBUTION_PARAMETERS = [
        'gclid', 'gbraid', 'wbraid',   // Google Ads
        'fbclid', 'msclkid',           // Meta, Microsoft Ads
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
    ];

    public function __construct(private readonly TrackingService $tracking)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $existing = $request->cookie('lst_vid');
        $arriving = $this->hasAttributionParameters($request);

        // Nothing to attribute and nobody to attribute it to — leave clean.
        if (! $existing && ! $arriving) {
            return $next($request);
        }

        $visitorId = $existing ?: (string) Str::uuid();

        // Expose to the view layer before the response is built, so the page
        // can tag its analytics payload with the same id the database holds.
        $request->attributes->set('listora_visitor_id', $visitorId);

        if ($arriving) {
            // firstOrCreate inside — a returning visitor keeps their original
            // source rather than having it overwritten by the latest click.
            $this->tracking->captureFirstTouch($request, $visitorId);
        }

        $response = $next($request);

        if (! $existing) {
            $response->headers->setCookie(
                cookie('lst_vid', $visitorId, self::COOKIE_MINUTES)
            );
        }

        return $response;
    }

    private function hasAttributionParameters(Request $request): bool
    {
        foreach (self::ATTRIBUTION_PARAMETERS as $parameter) {
            if (filled($request->query($parameter))) {
                return true;
            }
        }

        return false;
    }
}
