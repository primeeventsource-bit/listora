<?php

namespace App\Http\Controllers;

use App\Enums\DraftStatus;
use App\Enums\ListingStatus;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\ListingDraft;
use App\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * One route, three dashboards.
 *
 * Which one a user sees follows from what they are, not from a query
 * parameter: staff get the operations view, anyone advertising a listing gets
 * the owner view, everyone else gets the traveler view. Routing on role here
 * rather than on separate URLs means a link to /dashboard is safe to send to
 * anybody.
 */
class DashboardController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return $this->staff();
        }

        // "Owns a listing" is asked of the data, not of the role column. An
        // account created as a traveler that later advertises something should
        // see its listings without anyone having to remember to flip a role.
        $hasListings = Listing::query()->ownedBy($user->id)->exists();

        return $hasListings ? $this->owner($request) : $this->traveler($request);
    }

    private function staff(): View
    {
        return view('dashboard-admin', [
            'draftsAwaiting' => ListingDraft::query()->awaitingVerification()->count(),
            'draftsVerified' => ListingDraft::query()->where('status', DraftStatus::Verified)->count(),
            'listingsLive' => Listing::query()->where('status', ListingStatus::Active)->count(),
            'listingsExpiring' => Listing::query()
                ->where('status', ListingStatus::Active)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays((int) setting('listings.expiry_warning_days', 30)))
                ->count(),
            'offersOpen' => Offer::query()->open()->count(),
            'recentDrafts' => ListingDraft::query()->open()->latest()->limit(10)->get(),
        ]);
    }

    private function owner(Request $request): View
    {
        $userId = $request->user()->id;

        return view('dashboard-owner', [
            'listings' => Listing::query()
                ->ownedBy($userId)
                ->withCount(['offers', 'inquiries'])
                ->latest()
                ->limit(10)
                ->get(),
            'openOffers' => Offer::query()->forListingsOwnedBy($userId)->open()->count(),
            'unreadInquiries' => Inquiry::query()->forListingsOwnedBy($userId)->unread()->count(),
            'drafts' => ListingDraft::query()->where('owner_id', $userId)->open()->get(),
            'expiringSoon' => Listing::query()
                ->ownedBy($userId)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays((int) setting('listings.expiry_warning_days', 30)))
                ->get(),
        ]);
    }

    private function traveler(Request $request): View
    {
        return view('dashboard-traveler', [
            'offers' => Offer::query()
                ->where('buyer_user_id', $request->user()->id)
                ->with('listing:id,slug,title,owner_id')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }
}
