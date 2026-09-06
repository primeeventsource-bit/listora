<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * resort_name becomes property_name.
 *
 * The label was changed on the forms first, which fixed what a visitor reads
 * but not what a reviewer reads. The column name reaches the page as the
 * field's name attribute, and reaches the public listing JSON as
 * location.resort_name - so the word survived in the two places this site
 * actually gets read from.
 *
 * It is also simply the right name now. Listora advertises vacation
 * properties; "resort" described a catalogue that no longer exists, and a
 * column whose name contradicts what it holds misleads whoever reads the
 * schema next.
 *
 * The `resorts` table and listings.resort_id are deliberately untouched. They
 * are a real relation to a real record, they appear in no public output, and
 * folding them into a copy change would be a different piece of work.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rename('resort_name', 'property_name');
    }

    public function down(): void
    {
        $this->rename('property_name', 'resort_name');
    }

    private function rename(string $from, string $to): void
    {
        foreach (['listings', 'listing_drafts'] as $table) {
            if (! Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($from, $to) {
                $blueprint->renameColumn($from, $to);
            });
        }
    }
};
