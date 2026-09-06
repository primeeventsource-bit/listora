<?php

namespace App\Http\Controllers;

use App\Enums\DraftStatus;
use App\Http\Requests\StorePropertyInformationSheetRequest;
use App\Models\Listing;
use App\Models\ListingDraft;
use App\Services\Tracking\TrackingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The property information sheet — the short way in.
 *
 * /list-your-property asks an owner to write their own listing and choose a
 * plan before anyone has spoken to them. That suits someone who already knows
 * what they hold and what they want for it. This is for everyone else: the
 * essentials, then a specialist goes over the options.
 *
 * What it produces is a ListingDraft, not a record of its own. Both forms
 * describe the same event — someone claiming ownership of something they would
 * like advertised, unverified until a specialist says otherwise — and that
 * event already has a home, a status machine, and a review queue. A parallel
 * table would mean two inboxes, two half-worked states, and a verification
 * workflow that only one of them runs. `source` is what tells them apart.
 *
 * No payment, no account, no plan. The sheet's job ends at "we have your
 * details and someone will call".
 */
class PropertyInformationController extends Controller
{
    public function __construct(private readonly TrackingService $tracking)
    {
    }

    public function create(): View
    {
        return view('pages.property-information');
    }

    public function store(StorePropertyInformationSheetRequest $request): RedirectResponse
    {
        $draft = ListingDraft::create($request->validated() + [
            // Set here rather than posted. The sheet stopped asking "what are
            // you advertising?" because there was one answer to choose from,
            // and a category arriving from the browser is a category a
            // submission could claim that the site does not advertise.
            'kind' => Listing::KIND_HOME,
            'owner_id' => $request->user()?->id,
            'source' => ListingDraft::SOURCE_SHEET,
            'status' => DraftStatus::New,
            // Deliberately absent: `plan`. The sheet does not ask, so recording
            // one here would invent a choice the owner never made.
        ]);

        $this->tracking->recordFromRequest($request, 'listing_draft_submitted', [
            'reference' => $draft->reference,
            'source' => ListingDraft::SOURCE_SHEET,
            'kind' => $draft->kind,
        ]);

        return redirect()
            ->route('property-information.create')
            ->with('sheet_reference', $draft->reference);
    }
}
