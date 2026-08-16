<?php

namespace App\Services\Listings;

use App\Enums\DraftStatus;
use App\Models\ListingDraft;
use App\Models\User;
use App\Services\AdminAuditLogService;

/**
 * The ownership-verification workflow behind the admin review queue.
 *
 * Verification is recorded as a timestamp plus the person who did it, not a
 * boolean. "Verified" is a claim Listora makes publicly on every listing, so
 * it has to be answerable later: who confirmed this, and when.
 */
class DraftReviewService
{
    /** Mark a draft's ownership confirmed, clearing it for publication. */
    public function verify(ListingDraft $draft, User $actor, ?string $note = null): ListingDraft
    {
        $draft->forceFill([
            'status' => DraftStatus::Verified,
            'verified_at' => now(),
            'verified_by_user_id' => $actor->id,
            // A re-verified draft should not carry a stale decline on it.
            'decline_reason' => null,
            'declined_at' => null,
        ])->save();

        AdminAuditLogService::log(
            actor: $actor,
            action: 'draft.verify',
            subject: $draft,
            payload: ['reference' => $draft->reference, 'note' => $note],
            ipAddress: request()?->ip(),
        );

        return $draft;
    }

    /**
     * Decline a draft, recording why.
     *
     * The reason is required rather than optional: it is what the owner is
     * told, and a decline with no explanation generates a support ticket
     * instead of resolving anything.
     */
    public function decline(ListingDraft $draft, User $actor, string $reason): ListingDraft
    {
        $draft->forceFill([
            'status' => DraftStatus::Declined,
            'decline_reason' => $reason,
            'declined_at' => now(),
        ])->save();

        AdminAuditLogService::log(
            actor: $actor,
            action: 'draft.decline',
            subject: $draft,
            payload: ['reference' => $draft->reference, 'reason' => $reason],
            ipAddress: request()?->ip(),
        );

        return $draft;
    }

    /** Move a new submission into the verification queue. */
    public function beginReview(ListingDraft $draft): ListingDraft
    {
        if ($draft->status === DraftStatus::New) {
            $draft->forceFill(['status' => DraftStatus::PendingVerification])->save();
        }

        return $draft;
    }
}
