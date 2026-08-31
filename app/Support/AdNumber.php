<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Advertising numbers: YYYYMMDDHHMM, twelve digits.
 *
 * Two sequences. A member gets one when their account is created and keeps it
 * for the life of the advertising account; a listing gets its own when it is
 * created. A public advertising URL carries both, which is what makes
 * /ad/202608310253/202608310310 legible in a report - the first number says
 * whose advertising this is, the second says which property.
 *
 * Numbers are unique across BOTH sequences, not merely within each table. Per
 * table would have been enough for the database, and wrong for people: an
 * admin creating an advertiser and their first listing in one sitting - the
 * normal flow - would produce the same twelve digits for both, and a URL
 * reading /ad/202608310253/202608310253. A number that identifies exactly one
 * thing is the entire point of putting it in the URL.
 *
 * The format carries no seconds, so two rows created inside the same minute
 * would collide. Rather than widen the format - the twelve-digit shape is the
 * requirement - the generator walks forward a minute at a time until it finds
 * a free number. A number therefore means "created at or shortly after this
 * minute", not "created exactly at this minute", which is the honest reading
 * of a minute-resolution identifier anyway.
 */
class AdNumber
{
    /** YYYYMMDDHHMM */
    public const FORMAT = 'YmdHi';

    /** Give up rather than spin forever if the minute space is saturated. */
    private const MAX_WALK = 10_000;

    /**
     * Every table that draws from the shared number space.
     *
     * A new sequence must be added here as well as given its column, or it
     * will start handing out numbers that already identify something else.
     *
     * @var array<class-string<Model>, string>
     */
    private const SEQUENCES = [
        \App\Models\User::class => 'ad_number',
        \App\Models\Listing::class => 'ad_number',
    ];

    /**
     * The next number free across every sequence, at or after the given moment.
     *
     * @param  class-string<Model>  $modelClass  Which sequence this is for. Only
     *                                           affects nothing today - the space
     *                                           is shared - but names the caller
     *                                           in the exception if it runs out.
     */
    public static function for(string $modelClass, ?CarbonInterface $at = null): string
    {
        $moment = ($at ?? now())->copy();

        for ($step = 0; $step < self::MAX_WALK; $step++) {
            $candidate = $moment->format(self::FORMAT);

            if (! self::taken($candidate)) {
                return $candidate;
            }

            $moment->addMinute();
        }

        throw new RuntimeException(
            "Could not allocate an ad number for {$modelClass}: ".self::MAX_WALK.' consecutive minutes are taken.'
        );
    }

    /** Is this number already in use anywhere? */
    private static function taken(string $candidate): bool
    {
        foreach (self::SEQUENCES as $model => $column) {
            $exists = $model::query()
                ->withoutGlobalScopes()
                ->where($column, $candidate)
                ->exists();

            if ($exists) {
                return true;
            }
        }

        return false;
    }

    /** Is this a well-formed advertising number? Used to route /ad/{number}. */
    public static function looksValid(string $value): bool
    {
        return (bool) preg_match('/^\d{12}$/', $value);
    }

    /** "202608310253" -> "31 Aug 2026, 02:53". For reports and tooltips. */
    public static function describe(string $value): ?string
    {
        if (! self::looksValid($value)) {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('YmdHi', $value);

        return $parsed ? $parsed->format('j M Y, H:i') : null;
    }
}
