<?php

namespace App\Http\Controllers;

use App\Enums\AdEventType;
use App\Models\Listing;
use App\Models\User;
use App\Services\Advertising\AdEventRecorder;
use App\Support\AdNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public advertising URLs.
 *
 *   /ad/{member}            every live listing this advertiser is running
 *   /ad/{member}/{listing}  one advertised property
 *
 * These are the canonical addresses. /listing/{slug} still resolves and
 * redirects here permanently, so links already shared or indexed keep working
 * - the site is young enough that dropping them would cost little, but a
 * permanent URL that stopped resolving is exactly the thing a member would
 * have printed on something.
 *
 * Every visit is recorded. That is the point: the advertising record has to be
 * able to show that a member's advertisement was reachable and receiving
 * traffic, which means the visit is evidence and not an optimization.
 */
class AdController extends Controller
{
    public function __construct(private readonly AdEventRecorder $recorder)
    {
    }

    /** All of one advertiser's live listings. */
    public function member(Request $request, string $adNumber): View
    {
        $member = $this->memberOrFail($adNumber);

        $listings = Listing::published()
            ->ownedBy($member->id)
            ->sorted(null)
            ->get();

        $this->recorder->record($request, AdEventType::AdView);

        return view('pages.ad-member', [
            'member' => $member,
            'listings' => $listings,
        ]);
    }

    /** One advertised property. */
    public function show(Request $request, string $adNumber, string $listingNumber): View
    {
        $member = $this->memberOrFail($adNumber);

        $listing = Listing::published()
            ->ownedBy($member->id)
            ->where('ad_number', $listingNumber)
            ->first();

        // A listing that exists but belongs to a different advertiser is a 404
        // rather than a redirect to the right URL. The number pair is the
        // address; a mismatched pair is a wrong address, and quietly
        // correcting it would let anyone discover which listings sit under
        // which member by trying combinations.
        if (! $listing) {
            throw new NotFoundHttpException;
        }

        return $this->render($request, $listing);
    }

    /**
     * The old address.
     *
     * Redirects permanently to the canonical advertising URL when there is
     * one, and otherwise renders the listing here.
     *
     * The fallback is not defensive padding. owner_id is nullable and every
     * seeded listing has none, so a listing without an advertiser is an
     * ordinary state rather than a corrupt one - and such a listing has no
     * member number, so no /ad/{member}/{listing} address exists to send
     * anyone to. Redirecting them to browse instead would have taken every
     * listing page on the site off the air.
     */
    public function legacy(Request $request, Listing $listing): View|RedirectResponse
    {
        $ownerNumber = $listing->owner?->ad_number;

        if ($ownerNumber && $listing->ad_number) {
            // Not recorded here - the redirect target records it, and counting
            // both would double every visit arriving by an old link.
            return redirect()->route('ad.show', [
                'adNumber' => $ownerNumber,
                'listingNumber' => $listing->ad_number,
            ], 301);
        }

        return $this->render($request, $listing);
    }

    /** The listing page itself, however it was reached. */
    private function render(Request $request, Listing $listing): View
    {
        $listing->increment('views');

        $this->recorder->record($request, AdEventType::ListingView, $listing);

        $similar = Listing::published()
            ->where('id', '!=', $listing->id)
            ->where(fn ($q) => $q->where('region', $listing->region)->orWhere('kind', $listing->kind))
            ->sorted(null)
            ->take(3)
            ->get();

        return view('pages.listing', compact('listing', 'similar'));
    }

    private function memberOrFail(string $adNumber): User
    {
        if (! AdNumber::looksValid($adNumber)) {
            throw new NotFoundHttpException;
        }

        $member = User::query()->where('ad_number', $adNumber)->first();

        if (! $member) {
            throw new NotFoundHttpException;
        }

        return $member;
    }
}
