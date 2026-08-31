<?php

use App\Support\AdNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Member and listing advertising numbers.
 *
 * Two sequences, both YYYYMMDDHHMM. The member number identifies whose
 * advertising account this is and is meant to outlive any individual listing;
 * the listing number identifies the property. A public advertising URL carries
 * both.
 *
 * Existing rows are backfilled from created_at rather than from now(), so a
 * number keeps meaning "when this was created" for accounts that predate the
 * column. Backfilled in chronological order so the numbers ascend the way they
 * would have if the column had always existed, and the collision walk lands on
 * the nearest free later minute rather than scattering.
 *
 * The unique index is added after the backfill: adding it first would reject
 * the second row created in any minute that already had one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ad_number', 12)->nullable()->after('id');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->string('ad_number', 12)->nullable()->after('reference');
        });

        $this->backfill('users', \App\Models\User::class);
        $this->backfill('listings', \App\Models\Listing::class);

        Schema::table('users', function (Blueprint $table) {
            $table->unique('ad_number');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->unique('ad_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['ad_number']);
            $table->dropColumn('ad_number');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropUnique(['ad_number']);
            $table->dropColumn('ad_number');
        });
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function backfill(string $table, string $modelClass): void
    {
        DB::table($table)
            ->select('id', 'created_at')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $modelClass) {
                foreach ($rows as $row) {
                    $at = $row->created_at ? \Illuminate\Support\Carbon::parse($row->created_at) : now();

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['ad_number' => AdNumber::for($modelClass, $at)]);
                }
            });
    }
};
