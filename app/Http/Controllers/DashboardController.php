<?php

namespace App\Http\Controllers;

use App\Enums\DraftStatus;
use App\Enums\ListingStatus;
use App\Models\AdminAuditLog;
use App\Models\ContactMessage;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Models\ListingDraft;
use App\Models\Offer;
use App\Models\User;
use App\Services\Advertising\MemberPerformance;
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
            return $this->staff($request);
        }

        // "Owns a listing" is asked of the data, not of the role column. An
        // account created as a traveler that later advertises something should
        // see its listings without anyone having to remember to flip a role.
        $hasListings = Listing::query()->ownedBy($user->id)->exists();

        return $hasListings ? $this->owner($request) : $this->traveler($request);
    }

    /**
     * The operations console home.
     *
     * Every tile is gated on the permission its destination requires, and the
     * query behind it only runs if the viewer holds that permission. The old
     * version rendered a fixed five tiles for anyone who passed isStaff(), so
     * a role without `listings.view` was shown a live listing count and a link
     * to its own 403 — it leaked the number and then refused the page.
     *
     * Counting only what will be shown also keeps a Listing Specialist's
     * dashboard from running the four queries behind modules they cannot open.
     */
    private function staff(Request $request): View
    {
        $user = $request->user();

        $can = fn (string $permission) => $user->hasPermission($permission);

        $expiryWindow = now()->addDays((int) setting('listings.expiry_warning_days', 30));

        $tiles = [];

        if ($can('drafts.view')) {
            $tiles[] = [
                'label' => 'Awaiting verification',
                'value' => ListingDraft::query()->awaitingVerification()->count(),
                'url' => route('admin.drafts.index'),
                'tone' => 'urgent',
            ];
            $tiles[] = [
                'label' => 'Verified, ready to publish',
                'value' => ListingDraft::query()->where('status', DraftStatus::Verified)->count(),
                'url' => route('admin.drafts.index', ['status' => 'verified']),
                'tone' => null,
            ];
        }

        if ($can('listings.view')) {
            $tiles[] = [
                'label' => 'Live listings',
                'value' => Listing::query()->where('status', ListingStatus::Active)->count(),
                'url' => route('admin.listings.index'),
                'tone' => null,
            ];
            $tiles[] = [
                'label' => 'Terms ending soon',
                'value' => Listing::query()
                    ->where('status', ListingStatus::Active)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', $expiryWindow)
                    ->count(),
                'url' => route('admin.listings.index'),
                'tone' => 'warn',
            ];
        }

        if ($can('offers.view')) {
            $tiles[] = [
                'label' => 'Open offers',
                'value' => Offer::query()->open()->count(),
                'url' => route('admin.offers.index'),
                'tone' => null,
            ];
        }

        // The inbox counts existed in InboxController and were never surfaced
        // anywhere a person lands, so unanswered questions sat behind a tab
        // nobody had a reason to click.
        if ($can('inbox.view')) {
            $tiles[] = [
                'label' => 'Questions unanswered',
                'value' => ContactMessage::query()->where('status', ContactMessage::STATUS_NEW)->count(),
                'url' => route('admin.inbox.index'),
                'tone' => 'urgent',
            ];
        }

        if ($can('users.view')) {
            $tiles[] = [
                'label' => 'Accounts',
                'value' => User::query()->count(),
                'url' => route('admin.users.index'),
                'tone' => null,
            ];
        }

        if ($can('audit.view')) {
            $tiles[] = [
                'label' => 'Logged changes (7d)',
                'value' => AdminAuditLog::query()->where('occurred_at', '>=', now()->subDays(7))->count(),
                'url' => route('admin.audit.index'),
                'tone' => null,
            ];
        }

        return view('dashboard-admin', [
            'tiles' => $tiles,
            // The queue is the console's reason for existing, but only for
            // someone who can work it.
            'recentDrafts' => $can('drafts.view')
                ? ListingDraft::query()->open()->latest()->limit(10)->get()
                : collect(),
            'canSeeDrafts' => $can('drafts.view'),
            'recentActivity' => $can('audit.view')
                ? AdminAuditLog::query()->with('actor:id,name')->latest('occurred_at')->limit(8)->get()
                : collect(),
            // Listing performance cards. Ordered by views rather than recency
            // because the question this answers is "what is the advertising
            // actually doing", not "what changed last".
            'topListings' => $can('listings.view')
                ? Listing::query()
                    ->where('status', ListingStatus::Active)
                    ->withCount(['offers', 'inquiries'])
                    ->orderByDesc('views')
                    ->limit(8)
                    ->get()
                : collect(),
        ]);
    }

    /**
     * The advertiser's one screen.
     *
     * Performance used to be a separate page behind its own nav item, which
     * split the two halves of the same question - is my advertising running,
     * and is it doing anything - across two clicks. An advertiser signing in
     * to check on what they paid for should not have to know that the numbers
     * live somewhere else.
     */
    private function owner(Request $request): View
    {
        $userId = $request->user()->id;

        return view('dashboard-owner', app(MemberPerformance::class)->forRequest($request, $userId) + [
            'member' => $request->user(),
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
