<?php

namespace App\Enums;

/**
 * The three advertising plans.
 *
 * A plan is arranged directly with the owner — Listora does not take payment
 * on the website and holds no card, merchant, or processor data. What a plan
 * means to the application is editorial and quantitative: how long the
 * advertising runs, how many properties and photos it covers, and where it
 * sorts in browse.
 *
 * The dollar figures shown on /pricing live in config/listora.php with the
 * rest of the marketing copy. They are display values, not amounts anything
 * charges, so they deliberately do not appear here.
 *
 * Named Starter / Explorer / Signature. They were Essential / Featured /
 * Premier, and the old value `featured` was a particular problem: `listings`
 * also has an `is_featured` column, so a plan and a placement flag shared a
 * word while meaning different things.
 */
enum PlanTier: string
{
    case Starter = 'starter';
    case Explorer = 'explorer';
    case Signature = 'signature';

    public function label(): string
    {
        return match ($this) {
            self::Starter => 'Starter',
            self::Explorer => 'Explorer',
            self::Signature => 'Signature',
        };
    }

    /**
     * How long an advertising term runs.
     *
     * 180 days, not six months. The distinction is deliberate: a term
     * expressed in months drifts against the calendar, so two listings
     * published a day apart can get terms of different lengths depending on
     * which months they cross. Days give every advertiser the same term and
     * make the end date arithmetic anyone can check.
     */
    public function termDays(): int
    {
        return 180;
    }

    /**
     * How many property profiles the plan covers.
     *
     * Nothing enforces this yet — a plan is still recorded per listing, so
     * this is the published figure in one place rather than a limit the
     * application applies. Enforcement needs the plan to belong to the
     * advertiser rather than to the listing, which is a larger change than
     * the pricing page.
     */
    public function propertyLimit(): int
    {
        return match ($this) {
            self::Starter => 1,
            self::Explorer => 3,
            self::Signature => 5,
        };
    }

    /** Maximum photos the plan allows. */
    public function photoLimit(): int
    {
        return match ($this) {
            self::Starter => 20,
            self::Explorer, self::Signature => 40,
        };
    }

    /**
     * Sort weight for browse results — lower sorts first. Explorer and
     * Signature place above Starter; see Listing::scopeSorted.
     */
    public function placementRank(): int
    {
        return match ($this) {
            self::Signature => 0,
            self::Explorer => 1,
            self::Starter => 2,
        };
    }

    /** Does the plan carry the "Featured" badge on every card? */
    public function isFeatured(): bool
    {
        return $this !== self::Starter;
    }

    /** @return array<string, string> value => label, for form selects. */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
