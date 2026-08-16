<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DraftStatus;
use App\Http\Controllers\Controller;
use App\Models\ListingDraft;
use App\Services\Listings\DraftReviewService;
use App\Services\Listings\ListingPublisher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * The listing review queue: what the public wizard produces, and the ownership
 * verification that gates publication.
 *
 * Every state change here writes to admin_audit_logs through the services —
 * "who verified this listing's ownership, and when" is a question Listora has
 * to be able to answer, because it makes that claim publicly on every card.
 */
class DraftController extends Controller
{
    public function __construct(
        private readonly DraftReviewService $review,
        private readonly ListingPublisher $publisher,
    ) {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status');

        $drafts = ListingDraft::query()
            ->when(
                $status && $status !== 'all',
                fn ($q) => $q->where('status', $status),
                // The queue defaults to work outstanding rather than to
                // everything: a reviewer opening this page wants what still
                // needs a decision, not a year of resolved drafts.
                fn ($q) => $q->open(),
            )
            ->with('owner:id,name,email')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.drafts.index', [
            'drafts' => $drafts,
            'status' => $status ?: 'open',
            'statuses' => DraftStatus::cases(),
            'counts' => [
                'awaiting_verification' => ListingDraft::query()->awaitingVerification()->count(),
                'verified' => ListingDraft::query()->where('status', DraftStatus::Verified)->count(),
            ],
        ]);
    }

    public function show(ListingDraft $draft): View
    {
        // Opening a new submission moves it into the verification queue, so
        // the counts on the index reflect what is actually being worked.
        $this->review->beginReview($draft);

        return view('admin.drafts.show', [
            'draft' => $draft->load('owner', 'listing', 'verifiedBy'),
        ]);
    }

    public function verify(Request $request, ListingDraft $draft): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->review->verify($draft, $request->user(), $data['note'] ?? null);

        return back()->with('status', "Ownership verified for {$draft->reference}. It can be published now.");
    }

    public function decline(Request $request, ListingDraft $draft): RedirectResponse
    {
        $data = $request->validate([
            // Required, not optional: this text is what the owner is told, and
            // a decline with no explanation just becomes a support ticket.
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->review->decline($draft, $request->user(), $data['reason']);

        return redirect()
            ->route('admin.drafts.index')
            ->with('status', "Declined {$draft->reference}.");
    }

    public function publish(Request $request, ListingDraft $draft): RedirectResponse
    {
        try {
            $listing = $this->publisher->publish($draft, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['draft' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.listings.index')
            ->with('status', "Published {$draft->reference} as “{$listing->title}”.");
    }
}
