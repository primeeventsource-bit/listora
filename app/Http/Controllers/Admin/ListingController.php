<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingStatus;
use App\Enums\PlanTier;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Services\AdminAuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $listings = Listing::query()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $term) => $q->search($term))
            ->with('owner:id,name,email')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.listings.index', [
            'listings' => $listings,
            'statuses' => ListingStatus::cases(),
            'filters' => [
                'status' => $request->query('status', ''),
                'q' => $request->query('q', ''),
            ],
        ]);
    }

    public function edit(Listing $listing): View
    {
        return view('admin.listings.edit', [
            'listing' => $listing->load('owner'),
            'plans' => PlanTier::options(),
        ]);
    }

    public function update(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:12000'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:64'],
            'region' => ['required', 'string', 'max:96'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_unit' => ['required', Rule::in(['total', 'night', 'week', 'point'])],
        ]);

        $listing->update($data);

        AdminAuditLogService::log(
            actor: $request->user(),
            action: 'listing.update',
            subject: $listing,
            payload: ['changed' => array_keys($listing->getChanges())],
            ipAddress: $request->ip(),
        );

        return back()->with('status', 'Listing updated.');
    }

    public function publish(Request $request, Listing $listing): RedirectResponse
    {
        // Ownership verification is the promise every plan makes, so it gates
        // publication here too — not just on the wizard path. An admin
        // publishing straight from this screen must not be able to route
        // around the one check the public claim rests on.
        if (! $listing->verified_at && setting('listings.require_ownership_verification', true)) {
            return back()->withErrors([
                'listing' => 'Ownership has not been verified for this listing yet.',
            ]);
        }

        $publishedAt = $listing->published_at ?? now();
        $plan = $listing->plan ?? PlanTier::Starter;

        $listing->forceFill([
            'status' => ListingStatus::Active,
            'published_at' => $publishedAt,
            // Re-publishing a lapsed listing starts a fresh term from today
            // rather than restoring an end date that is already in the past.
            'expires_at' => $listing->hasExpired() || ! $listing->expires_at
                ? now()->addDays($plan->termDays())
                : $listing->expires_at,
        ])->save();

        AdminAuditLogService::log(
            actor: $request->user(),
            action: 'listing.publish',
            subject: $listing,
            ipAddress: $request->ip(),
        );

        return back()->with('status', 'Listing is live.');
    }

    public function unpublish(Request $request, Listing $listing): RedirectResponse
    {
        $listing->forceFill(['status' => ListingStatus::Paused])->save();

        AdminAuditLogService::log(
            actor: $request->user(),
            action: 'listing.unpublish',
            subject: $listing,
            ipAddress: $request->ip(),
        );

        return back()->with('status', 'Listing paused and removed from browse.');
    }

    /**
     * Set a listing's plan tier.
     *
     * The plan controls term length, photo allowance, and placement — nothing
     * is charged here. Plans are arranged with the owner off the site, so this
     * screen records a commercial decision that was made elsewhere.
     */
    public function assignPlan(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::enum(PlanTier::class)],
            'extend_term' => ['nullable', 'boolean'],
        ]);

        $plan = PlanTier::from($data['plan']);
        $previous = $listing->plan?->value;

        $listing->forceFill([
            'plan' => $plan,
            'is_featured' => $plan->isFeatured(),
            'expires_at' => $request->boolean('extend_term')
                ? now()->addDays($plan->termDays())
                : $listing->expires_at,
        ])->save();

        AdminAuditLogService::log(
            actor: $request->user(),
            action: 'listing.assign_plan',
            subject: $listing,
            payload: ['from' => $previous, 'to' => $plan->value],
            ipAddress: $request->ip(),
        );

        return back()->with('status', "Plan set to {$plan->label()}.");
    }
}
