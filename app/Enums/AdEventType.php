<?php

namespace App\Enums;

/**
 * Everything the activity log records.
 *
 * Two kinds of event live here. The first nine are the advertising engagement
 * funnel, ordered as the brief describes it: a visit reaches an
 * advertisement, the listing is read, details are opened, an inquiry or offer
 * is started, an account is created or signed into, the inquiry or offer is
 * submitted, and a peer-to-peer conversation begins.
 *
 * The rest are the material actions an audit trail has to be able to answer
 * for - navigation, sessions, listing changes, agreement acceptance, payment
 * pages. They are not funnel steps and report stage 0, which is what keeps
 * them out of the advertiser's conversion figures. A page view of the help
 * centre is not a step towards an inquiry, and counting it as one would
 * quietly wreck every funnel on the site.
 *
 * The funnel order matters to reporting, not to the database - a real visitor
 * skips steps and doubles back. stage() gives each type its position so a
 * funnel can be counted without hard-coding the sequence in three reports.
 */
enum AdEventType: string
{
    // ---- The advertising funnel.
    case AdView = 'ad_view';
    case ListingView = 'listing_view';
    case DetailsViewed = 'details_viewed';
    case PhotosViewed = 'photos_viewed';
    case InquiryStarted = 'inquiry_started';
    case OfferStarted = 'offer_started';
    case AccountCreated = 'account_created';
    case InquirySubmitted = 'inquiry_submitted';
    case OfferSubmitted = 'offer_submitted';
    case MessageStarted = 'message_started';
    case MessageSent = 'message_sent';

    // ---- Everything else the audit trail answers for.
    case PageView = 'page_view';
    case SignedIn = 'signed_in';
    case SignedOut = 'signed_out';
    case ListingCreated = 'listing_created';
    case ListingUpdated = 'listing_updated';
    case DraftSubmitted = 'draft_submitted';
    case AgreementAccepted = 'agreement_accepted';
    case CheckoutViewed = 'checkout_viewed';
    case PaymentAuthorized = 'payment_authorized';
    case AccountUpdated = 'account_updated';
    case AdminAction = 'admin_action';

    public function label(): string
    {
        return match ($this) {
            self::AdView => 'Advertisement viewed',
            self::ListingView => 'Listing viewed',
            self::DetailsViewed => 'Property details viewed',
            self::PhotosViewed => 'Photos viewed',
            self::InquiryStarted => 'Inquiry started',
            self::OfferStarted => 'Offer started',
            self::AccountCreated => 'Account created',
            self::InquirySubmitted => 'Inquiry submitted',
            self::OfferSubmitted => 'Offer submitted',
            self::MessageStarted => 'Message started',
            self::MessageSent => 'Message sent',

            self::PageView => 'Page viewed',
            self::SignedIn => 'Signed in',
            self::SignedOut => 'Signed out',
            self::ListingCreated => 'Listing created',
            self::ListingUpdated => 'Listing edited',
            self::DraftSubmitted => 'Advertising request submitted',
            self::AgreementAccepted => 'Agreement accepted',
            self::CheckoutViewed => 'Payment page viewed',
            self::PaymentAuthorized => 'Payment authorized',
            self::AccountUpdated => 'Account details changed',
            self::AdminAction => 'Administrator action',
        };
    }

    /**
     * Position in the funnel, 1-based. Several types share a stage.
     *
     * Zero means "not a funnel step". Those events are recorded and shown in
     * the activity log, and never counted towards a conversion figure.
     */
    public function stage(): int
    {
        return match ($this) {
            self::AdView => 1,
            self::ListingView => 2,
            self::DetailsViewed, self::PhotosViewed => 3,
            self::InquiryStarted, self::OfferStarted => 4,
            self::AccountCreated => 5,
            self::InquirySubmitted, self::OfferSubmitted => 6,
            self::MessageStarted, self::MessageSent => 7,
            default => 0,
        };
    }

    /**
     * Types that count as the advertisement being seen.
     *
     * "Advertisement views" on a member dashboard means this set, not every
     * row in the table - counting inquiry submissions or help-centre page
     * views as views would inflate the number the advertiser pays attention
     * to, and it is the number they are buying.
     */
    public static function views(): array
    {
        return [self::AdView, self::ListingView];
    }

    /**
     * Events that belong in an evidentiary export: account creation, terms
     * and agreement acceptance, sign-in history, listing activation, payment
     * authorization and account changes.
     *
     * Named as a set because "which events would we produce in a dispute" is
     * a question with one answer, and three screens deciding it separately is
     * how one of them ends up producing a different record than another.
     *
     * @return array<int, self>
     */
    public static function evidentiary(): array
    {
        return [
            self::AccountCreated,
            self::AccountUpdated,
            self::SignedIn,
            self::SignedOut,
            self::AgreementAccepted,
            self::ListingCreated,
            self::ListingUpdated,
            self::DraftSubmitted,
            self::InquirySubmitted,
            self::OfferSubmitted,
            self::CheckoutViewed,
            self::PaymentAuthorized,
        ];
    }

    /** @return array<string, string> value => label, for filter selects. */
    public static function options(): array
    {
        $out = [];

        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
