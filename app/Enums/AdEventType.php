<?php

namespace App\Enums;

/**
 * The advertising engagement funnel.
 *
 * Ordered as the brief describes it: a visit reaches an advertisement, the
 * listing is read, details are opened, an inquiry or offer is started, an
 * account is created or signed into, the inquiry or offer is submitted, and a
 * peer-to-peer conversation begins.
 *
 * The order matters to reporting, not to the database - a real visitor skips
 * steps and doubles back. `stage()` gives each type its position so a funnel
 * can be counted without hard-coding the sequence in three different reports.
 */
enum AdEventType: string
{
    case AdView = 'ad_view';
    case ListingView = 'listing_view';
    case DetailsViewed = 'details_viewed';
    case InquiryStarted = 'inquiry_started';
    case OfferStarted = 'offer_started';
    case AccountCreated = 'account_created';
    case InquirySubmitted = 'inquiry_submitted';
    case OfferSubmitted = 'offer_submitted';
    case MessageStarted = 'message_started';

    public function label(): string
    {
        return match ($this) {
            self::AdView => 'Advertisement viewed',
            self::ListingView => 'Listing viewed',
            self::DetailsViewed => 'Property details viewed',
            self::InquiryStarted => 'Inquiry started',
            self::OfferStarted => 'Offer started',
            self::AccountCreated => 'Account created',
            self::InquirySubmitted => 'Inquiry submitted',
            self::OfferSubmitted => 'Offer submitted',
            self::MessageStarted => 'Message started',
        };
    }

    /** Position in the funnel, 1-based. Several types share a stage. */
    public function stage(): int
    {
        return match ($this) {
            self::AdView => 1,
            self::ListingView => 2,
            self::DetailsViewed => 3,
            self::InquiryStarted, self::OfferStarted => 4,
            self::AccountCreated => 5,
            self::InquirySubmitted, self::OfferSubmitted => 6,
            self::MessageStarted => 7,
        };
    }

    /**
     * Types that count as the advertisement being seen.
     *
     * "Advertisement views" on a member dashboard means this set, not every
     * row in the table - counting inquiry submissions as views would inflate
     * the number the advertiser is paying attention to.
     */
    public static function views(): array
    {
        return [self::AdView, self::ListingView];
    }
}
