<?php

namespace App\Enums;

/**
 * Routing target for a /contact submission. Kept in code rather than a DB enum
 * so adding a department is a one-line change with no migration.
 */
enum ContactDepartment: string
{
    case General = 'general';
    case Listings = 'listings';
    case Advertising = 'advertising';
    case ClubPoints = 'club_points';
    case Technical = 'technical';
    case Billing = 'billing';
    case Business = 'business';
    case Media = 'media';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General Support',
            self::Listings => 'Property Listings',
            self::Advertising => 'Advertising Plans',
            self::ClubPoints => 'Club Points & Weeks',
            self::Technical => 'Technical Support',
            self::Billing => 'Billing',
            self::Business => 'Business Inquiries',
            self::Media => 'Media',
        };
    }

    /** @return array<string, string> value => label, for a <select>. */
    /**
     * Departments offered on the contact form.
     *
     * ClubPoints is withheld while those categories are - a "Club Points &
     * Weeks" option in a public dropdown advertises a service the site does
     * not currently provide, and it was one of the things a payment
     * underwriter read when classifying the business.
     *
     * The case stays defined so existing messages filed against it keep their
     * label in the console, and so it returns with the categories.
     */
    public static function options(): array
    {
        $out = [];

        foreach (self::cases() as $case) {
            if ($case === self::ClubPoints && ! \App\Models\Listing::timeshareOffered()) {
                continue;
            }

            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
