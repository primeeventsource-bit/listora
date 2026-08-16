<?php

namespace App\Enums;

/**
 * What a support request is about.
 *
 * There is deliberately no "booking or check-in" or "cancellation" option.
 * Offering them would tell people Listora holds their reservation and can
 * cancel it, which is the opposite of how Listora works: owners and travelers
 * settle directly and Listora is never a party to the stay. Those requests
 * would also route to a queue that could not resolve them.
 *
 * A stay problem is still a real problem, so ReachingAnOwner exists to catch
 * it honestly — we can help someone make contact and we hold the record of
 * the inquiry, but we cannot cancel, refund, or relocate.
 */
enum SupportCategory: string
{
    case ReachingAnOwner = 'reaching_an_owner';
    case Listing = 'listing';
    case Offer = 'offer';
    case Verification = 'verification';
    case Billing = 'billing';
    case Account = 'account';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ReachingAnOwner => "I can't reach a listing owner",
            self::Listing => 'A property listing',
            self::Offer => 'An inquiry or offer',
            self::Verification => 'Ownership verification',
            self::Billing => 'Advertising plan billing',
            self::Account => 'Account access',
            self::Other => 'Something else',
        };
    }

    /**
     * Requests that should not sit in a queue overnight.
     *
     * Someone who cannot reach an owner may have travel planned around it,
     * and billing means money has moved through Listora's own merchant
     * account — the only money that ever does.
     */
    public function priority(): string
    {
        return match ($this) {
            self::ReachingAnOwner, self::Billing => 'high',
            default => 'normal',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
