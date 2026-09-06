<?php

namespace App\Enums;

/**
 * The three advertising plans.
 *
 * A plan is arranged directly with the owner — Listora does not take payment
 * on the website and holds no card, merchant, or processor data. What a plan
 * means to the application is therefore entirely editorial: how long the
 * listing runs, how many photos it may show, and where it sorts in browse.
 *
 * The dollar figures shown on /pricing live in config/listora.php with the
 * rest of the marketing copy. They are display values, not amounts anything
 * charges, so they deliberately do not appear here.
 */
enum PlanTier: string
{
    case Essential = 'essential';
    case Featured = 'featured';
    case Premier = 'premier';

    public function label(): string
    {
        return match ($this) {
            self::Essential => 'Essential',
            self::Featured => 'Featured',
            self::Premier => 'Premier',
        };
    }

    /** How many months of advertising the plan runs for. */
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

    /** Maximum photos the plan allows. */
    public function photoLimit(): int
    {
        return match ($this) {
            self::Essential => 20,
            self::Featured, self::Premier => 40,
        };
    }

    /**
     * Sort weight for browse results — lower sorts first. Featured and
     * Premier place above Essential; see Listing::scopeSorted.
     */
    public function placementRank(): int
    {
        return match ($this) {
            self::Premier => 0,
            self::Featured => 1,
            self::Essential => 2,
        };
    }

    /** Does the plan carry the "Featured" badge on every card? */
    public function isFeatured(): bool
    {
        return $this !== self::Essential;
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
