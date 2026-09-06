<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Existing bot rows become desktop rows.
 *
 * UserAgent now classifies automated clients as desktop, which fixes what
 * gets written from here on and does nothing for what is already there.
 * Without this, reporting reads as though crawler traffic stopped on the day
 * of the deploy - and every screen would still show a Bot row for older
 * periods while showing none for recent ones.
 *
 * No rows are deleted and no counts change. This only rewrites the label on
 * traffic that was already being counted.
 *
 * Not reversible in any meaningful sense: the events that used to say 'bot'
 * are indistinguishable from desktop ones once merged, so down() is a no-op
 * rather than a lie about being able to separate them again.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['ad_events', 'tracking_events'] as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            if (! DB::getSchemaBuilder()->hasColumn($table, 'device_category')) {
                continue;
            }

            DB::table($table)->where('device_category', 'bot')->update(['device_category' => 'desktop']);
        }
    }

    public function down(): void
    {
        // Deliberately empty. See the note above.
    }
};
