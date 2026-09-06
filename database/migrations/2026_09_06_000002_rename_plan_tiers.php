<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Essential / Featured / Premier become Starter / Explorer / Signature.
 *
 * The stored value is renamed rather than mapped in the enum, so the database
 * says what the site says. A plan value of 'premier' behind a page selling
 * "Signature" is the kind of gap where a report, an export, or an admin filter
 * quietly disagrees with the price the advertiser was shown.
 *
 * 'featured' is the one worth losing outright: `listings` also carries an
 * `is_featured` flag for placement, so the plan and the flag shared a word
 * while meaning different things.
 *
 * Reversible - down() puts the old values back.
 */
return new class extends Migration
{
    private const RENAMES = [
        'essential' => 'starter',
        'featured' => 'explorer',
        'premier' => 'signature',
    ];

    public function up(): void
    {
        $this->rewrite(self::RENAMES, 'starter', 'explorer');
    }

    public function down(): void
    {
        $this->rewrite(array_flip(self::RENAMES), 'essential', 'featured');
    }

    /**
     * @param  array<string, string>  $map
     */
    private function rewrite(array $map, string $listingDefault, string $draftDefault): void
    {
        foreach (['listings', 'listing_drafts'] as $table) {
            foreach ($map as $from => $to) {
                DB::table($table)->where('plan', $from)->update(['plan' => $to]);
            }
        }

        // The column defaults name a plan too, and a default nobody updated
        // would start writing a value the enum cannot cast.
        Schema::table('listings', function (Blueprint $table) use ($listingDefault) {
            $table->string('plan')->default($listingDefault)->change();
        });

        // listing_drafts is nullable on purpose: the information sheet does
        // not ask for a plan, so its rows record no choice. Only the default
        // moves here, not the nullability.
        Schema::table('listing_drafts', function (Blueprint $table) use ($draftDefault) {
            $table->string('plan')->nullable()->default(null)->change();
        });

        unset($draftDefault);
    }
};
