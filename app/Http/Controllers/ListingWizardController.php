<?php

namespace App\Http\Controllers;

use App\Enums\DraftStatus;
use App\Http\Requests\StoreListingDraftRequest;
use App\Models\ListingDraft;
use App\Services\Tracking\TrackingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * The public "list your property" wizard.
 *
 * Deliberately anonymous-friendly: an owner should not have to create an
 * account to ask about advertising. What the wizard produces is a draft, never
 * a listing — drafts live in their own table precisely so no public query can
 * surface unverified content by accident.
 *
 * Nothing here takes payment. Plans are arranged with the owner directly, so
 * the wizard's job ends at "we have your details and will be in touch".
 */
class ListingWizardController extends Controller
{
    public function __construct(private readonly TrackingService $tracking)
    {
    }

    public function create(): View
    {
        return view('pages.list', [
            'plans' => config('listora.plans'),
            'regions' => config('listora.regions'),
        ]);
    }

    public function store(StoreListingDraftRequest $request): RedirectResponse
    {
        $draft = ListingDraft::create($request->validated() + [
            // A signed-in owner's draft is linked to them so it appears on
            // their dashboard; an anonymous one is claimed later by email.
            'owner_id' => $request->user()?->id,
            'status' => DraftStatus::New,
        ]);

        // Recorded because "how many people started advertising with us this
        // week" is a question the business asks constantly, and the answer
        // is otherwise only inferable from rows that reviewers mutate.
        $this->tracking->recordFromRequest($request, 'listing_draft_submitted', [
            'reference' => $draft->reference,
            'plan' => $draft->plan?->value,
            'kind' => $draft->kind,
        ]);

        return redirect()
            ->route('list.create')
            ->with('draft', $draft->reference)
            ->with('plan', $draft->planTier()->label());
    }
}
