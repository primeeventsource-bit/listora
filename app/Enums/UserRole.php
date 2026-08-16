<?php

namespace App\Enums;

/**
 * The primary role column on `users`.
 *
 * Listora has two customer-facing personas rather than Vaytoven's three:
 * a **traveler** browses and makes offers, an **owner** pays to advertise a
 * property, points package, or week. There is no "member" tier because
 * Listora sells advertising, not membership.
 *
 * ListingSpecialist is staff but deliberately NOT an admin — see
 * EnsureListingSpecialist. It exists so the Premier plan's "dedicated listing
 * specialist" can work the review queue without holding the console.
 */
enum UserRole: string
{
    case Traveler = 'traveler';
    case Owner = 'owner';
    case ListingSpecialist = 'listing_specialist';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function isAdmin(): bool
    {
        return match ($this) {
            self::Admin, self::SuperAdmin => true,
            default => false,
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function isListingSpecialist(): bool
    {
        return $this === self::ListingSpecialist;
    }

    public function label(): string
    {
        return match ($this) {
            self::Traveler => 'Traveler',
            self::Owner => 'Owner',
            self::ListingSpecialist => 'Listing Specialist',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }
}
