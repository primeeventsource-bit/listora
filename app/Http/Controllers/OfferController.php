<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfferRequest;
use App\Models\Listing;
use App\Models\Offer;
use App\Services\Offers\OfferService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Buyer-side submission and owner-side resolution of offers.
 *
 * Owner scoping runs through Offer::scopeForListingsOwnedBy, which joins
 * `listings.owner_id` rather than reading the denormalised `owner_user_id`
 * column — a listing that changes hands must not leave its offers readable by
 * the previous owner.
 */
class OfferController extends Controller
{
    public function __construct(private readonly OfferService $offers)
    {
    }

    /** Public: a traveler submits an inquiry or priced offer on a listing. */
    public function store(StoreOfferRequest $request, Listing $listing): RedirectResponse
    {
        abort_unless($listing->isPubliclyVisible(), 404);

        $offer = $this->offers->submit(
            listing: $listing,
            data: $request->validated() + ['offer_amount_cents' => $request->offerAmountCents()],
            buyer: $request->user(),
            request: $request,
        );

        return back()->with(
            'sent',
            "Sent to {$listing->owner_name}. Your reference is {$offer->reference} — "
            .'replies come straight to your inbox, because Listora never sits in the '
            .'middle of the conversation.',
        );
    }

    /** Owner: the queue of what their listings have attracted. */
    public function index(Request $request): View
    {
        $offers = Offer::query()
            ->forListingsOwnedBy($request->user()->id)
            ->with('listing:id,slug,title,owner_id')
            ->latest()
            ->paginate(20);

        return view('owner.offers', ['offers' => $offers]);
    }

    public function accept(Request $request, Offer $offer): RedirectResponse
    {
        return $this->respond($request, $offer, accept: true);
    }

    public function decline(Request $request, Offer $offer): RedirectResponse
    {
        return $this->respond($request, $offer, accept: false);
    }

    private function respond(Request $request, Offer $offer, bool $accept): RedirectResponse
    {
        $this->authorizeOwner($request, $offer);

        try {
            $accept
                ? $this->offers->accept($offer, $request->user())
                : $this->offers->decline($offer, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['offer' => $e->getMessage()]);
        }

        return back()->with('status', $accept
            // Said plainly, because the alternative is an owner believing dates
            // are now held. They are not; nothing here reserves anything.
            ? "Accepted. We've shared your contact details with {$offer->name} — "
              .'the two of you arrange dates and payment directly from here.'
            : 'Declined. We let them know.');
    }

    /**
     * A 404 rather than a 403 on someone else's offer: confirming that a
     * reference exists but belongs to another owner is an enumeration oracle,
     * and offer references are quoted aloud to support.
     */
    private function authorizeOwner(Request $request, Offer $offer): void
    {
        $owns = Offer::query()
            ->whereKey($offer->getKey())
            ->forListingsOwnedBy($request->user()->id)
            ->exists();

        abort_unless($owns, 404);
    }
}
