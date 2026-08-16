<?php

namespace App\Http\Controllers\Owner;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Listing;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * What an owner can do with the listings they advertise.
 *
 * Every action re-resolves the listing through `owner_id` rather than trusting
 * route-model binding alone — the slug is public, so binding by itself proves
 * only that the listing exists, never that this user owns it.
 */
class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $listings = Listing::query()
            ->ownedBy($request->user()->id)
            ->withCount(['offers', 'inquiries'])
            ->latest()
            ->paginate(20);

        return view('owner.listings', ['listings' => $listings]);
    }

    public function edit(Request $request, Listing $listing): View
    {
        $this->authorizeOwner($request, $listing);

        return view('owner.listing-edit', ['listing' => $listing]);
    }

    /**
     * An owner edits their own copy.
     *
     * Deliberately narrow: title, description, photos, and asking price only.
     * Status, plan, verification, and term dates are absent because they
     * encode what operations confirmed and what was commercially agreed —
     * an owner who could set `status` could publish an unverified listing.
     */
    public function update(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorizeOwner($request, $listing);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:40', 'max:12000'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_unit' => ['required', Rule::in(['total', 'night', 'week', 'point'])],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:96'],
            'photos' => ['nullable', 'array', 'max:'.$listing->plan?->photoLimit()],
            'photos.*' => ['string', 'url', 'max:512'],
        ]);

        $listing->update($data);

        return back()->with('status', 'Listing updated.');
    }

    public function pause(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorizeOwner($request, $listing);

        $listing->forceFill(['status' => ListingStatus::Paused])->save();

        return back()->with('status', 'Listing paused. It is no longer shown in browse.');
    }

    /**
     * Bring a paused listing back.
     *
     * Only from Paused, and only while the term still has time on it. A listing
     * whose term has run out needs a renewed plan, which is arranged with
     * Listora directly rather than restored with a button here.
     */
    public function resume(Request $request, Listing $listing): RedirectResponse
    {
        $this->authorizeOwner($request, $listing);

        if ($listing->status !== ListingStatus::Paused) {
            return back()->withErrors(['listing' => 'Only a paused listing can be resumed.']);
        }

        if ($listing->hasExpired()) {
            return back()->withErrors([
                'listing' => 'This listing\'s advertising term has ended. Contact us to renew it.',
            ]);
        }

        $listing->forceFill(['status' => ListingStatus::Active])->save();

        return back()->with('status', 'Listing is live again.');
    }

    /** Messages travelers have sent about this owner's listings. */
    public function inquiries(Request $request): View
    {
        $inquiries = Inquiry::query()
            ->forListingsOwnedBy($request->user()->id)
            ->with('listing:id,slug,title,owner_id')
            ->latest()
            ->paginate(25);

        // Opening the queue marks what is on this page as read. Scoped to the
        // ids actually rendered so pagination doesn't silently clear unread
        // messages the owner never saw.
        Inquiry::query()
            ->whereIn('id', $inquiries->pluck('id'))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('owner.inquiries', ['inquiries' => $inquiries]);
    }

    /**
     * 404 rather than 403 on someone else's listing: the slug is public, so
     * a 403 would confirm the listing exists while a 404 says nothing.
     */
    private function authorizeOwner(Request $request, Listing $listing): void
    {
        abort_unless($listing->owner_id === $request->user()->id, 404);
    }
}
