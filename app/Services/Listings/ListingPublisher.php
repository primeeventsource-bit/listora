<?php

namespace App\Services\Listings;

use App\Enums\DraftStatus;
use App\Enums\ListingStatus;
use App\Enums\PlanTier;
use App\Models\Listing;
use App\Models\ListingDraft;
use App\Models\User;
use App\Services\AdminAuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Promotes a verified listing draft into a live listing.
 *
 * This is the only path from the public wizard to a publicly visible listing,
 * and it is deliberately narrow: a draft that has not been ownership-verified
 * cannot pass through it. That check is the promise every advertising plan
 * makes ("Ownership verified before publishing"), so it is enforced here in
 * the service rather than left to whichever controller happens to call it.
 *
 * The whole promotion is one transaction. A draft that is marked published
 * without its listing actually existing would drop out of the review queue
 * while never appearing on the site — invisible to both operations and the
 * owner, which is the worst possible failure for a paying customer.
 */
class ListingPublisher
{
    public function __construct(private readonly SlugFactory $slugs)
    {
    }

    /**
     * @throws RuntimeException when the draft is not ready to publish
     */
    public function publish(ListingDraft $draft, User $actor): Listing
    {
        if ($draft->status === DraftStatus::Published && $draft->listing) {
            // Idempotent: a double-submit from the review queue returns the
            // listing that already exists rather than creating a second one.
            return $draft->listing;
        }

        if (! $draft->isReadyToPublish()) {
            throw new RuntimeException(
                "Draft {$draft->reference} is not ready to publish: ownership must be verified first."
            );
        }

        return DB::transaction(function () use ($draft, $actor) {
            $plan = $draft->planTier();

            $listing = Listing::create($this->attributesFrom($draft, $plan));

            $draft->forceFill([
                'listing_id' => $listing->id,
                'status' => DraftStatus::Published,
                'published_at' => now(),
            ])->save();

            AdminAuditLogService::log(
                actor: $actor,
                action: 'listing.publish',
                subject: $listing,
                payload: [
                    'draft_reference' => $draft->reference,
                    'plan' => $plan->value,
                    'term_days' => $plan->termDays(),
                ],
                ipAddress: request()?->ip(),
            );

            return $listing;
        });
    }

    /**
     * Map a draft onto a listing row.
     *
     * The draft's free-text fields are carried across as-is; what the service
     * decides are the fields the owner does not get to set — status, term
     * dates, verification, and placement — because those encode what was paid
     * for and what operations confirmed.
     */
    private function attributesFrom(ListingDraft $draft, PlanTier $plan): array
    {
        $title = $draft->title ?: $this->fallbackTitle($draft);
        $publishedAt = now();

        return [
            'owner_id' => $draft->owner_id,
            'listing_draft_id' => $draft->id,
            'reference' => 'LST-'.strtoupper(Str::random(6)),
            'slug' => $this->slugs->uniqueFor($title),

            'kind' => $draft->kind,
            'mode' => $draft->mode,
            'title' => $title,
            'description' => $draft->description ?: '',

            'resort_name' => $draft->resort_name,
            'club_name' => $draft->club_name,
            'city' => $draft->city ?: '',
            'state' => $draft->state,
            'region' => $draft->region ?: '',

            'bedrooms' => $draft->bedrooms ?? 0,
            'sleeps' => $draft->sleeps ?? 2,
            'points' => $draft->points,
            'week_number' => $draft->week_number,
            'season' => $draft->season,

            'price' => $draft->price ?? 0,
            'price_unit' => $draft->price_unit ?: 'total',

            'plan' => $plan,
            'is_featured' => $plan->isFeatured(),
            'is_verified' => true,
            'owner_name' => $draft->owner_name,

            'status' => ListingStatus::Active,
            'verified_at' => $draft->verified_at,
            'verified_by_user_id' => $draft->verified_by_user_id,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->copy()->addDays($plan->termDays()),
        ];
    }

    /**
     * A listing with no title still needs one a traveler can read. Built from
     * what the draft does have rather than left blank or set to "Untitled",
     * which would be published to the public site.
     */
    private function fallbackTitle(ListingDraft $draft): string
    {
        $parts = array_filter([
            $draft->resort_name ?: $draft->club_name,
            $draft->city,
        ]);

        return $parts === []
            ? 'Vacation listing '.$draft->reference
            : implode(' — ', $parts);
    }
}
