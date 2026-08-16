<?php

namespace App\Enums;

/**
 * Status of a listing draft submitted through the "list your property" wizard.
 *
 * Ownership verification is its own state rather than a boolean because it is
 * the promise the plans make ("Ownership verified before publishing") and
 * because a draft can fail it and come back.
 *
 * There is deliberately no payment state. Listora does not take payment on the
 * website — plans are arranged directly with the owner — so a draft's path to
 * publication runs entirely through verification and editorial review.
 */
enum DraftStatus: string
{
    case New = 'new';
    case PendingVerification = 'pending_verification';
    case Verified = 'verified';
    case Published = 'published';
    case Declined = 'declined';

    /** Is this draft still moving through the pipeline? */
    public function isOpen(): bool
    {
        return match ($this) {
            self::Published, self::Declined => false,
            default => true,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::PendingVerification => 'Pending verification',
            self::Verified => 'Ownership verified',
            self::Published => 'Published',
            self::Declined => 'Declined',
        };
    }
}
