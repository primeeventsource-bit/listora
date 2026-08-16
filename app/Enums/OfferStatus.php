<?php

namespace App\Enums;

/**
 * Status of a buyer's offer or inquiry on a listing.
 *
 * Vaytoven's equivalent carried two open states because offers flowed in two
 * directions. On Listora they only ever flow one way — a traveler submits,
 * the listing owner responds — so there is a single open state.
 */
enum OfferStatus: string
{
    case Active = 'active';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    /** Still awaiting a decision, and therefore still eligible to expire. */
    public function isOpen(): bool
    {
        return $this === self::Active;
    }

    /** Open offers are the only ones anyone can accept or decline. */
    public function isActionable(): bool
    {
        return $this->isOpen();
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
