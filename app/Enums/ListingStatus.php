<?php

namespace App\Enums;

/**
 * Lifecycle of a listing.
 *
 * The path is Draft -> PendingReview -> Active, and the gate between the last
 * two is BOTH ownership verification and a paid order — see
 * ListingPublisher. Expired is distinct from Archived because an advertising
 * term ends on a date: an expired listing stops being public but stays
 * renewable, while an archived one is gone for good.
 */
enum ListingStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Active = 'active';
    case Paused = 'paused';
    case Expired = 'expired';
    case Archived = 'archived';

    /** Only Active listings appear in browse, search, and the sitemap. */
    public function isPublic(): bool
    {
        return $this === self::Active;
    }

    /** Can the owner still bring this listing back without buying a new plan? */
    public function isRenewable(): bool
    {
        return match ($this) {
            self::Paused, self::Expired => true,
            default => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingReview => 'Pending review',
            self::Active => 'Live',
            self::Paused => 'Paused',
            self::Expired => 'Expired',
            self::Archived => 'Archived',
        };
    }
}
