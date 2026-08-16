<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a draft say which door it came through.
 *
 * There are now two: the full wizard at /list-your-property, where an owner
 * picks a plan and fills in the unit facts themselves, and the short
 * information sheet at /property-information, where they leave the essentials
 * and a specialist goes over the options with them.
 *
 * Both produce the same thing — an unverified claim of ownership that a
 * specialist has to work — so both land in the same review queue. Giving the
 * sheet its own table would put one business event in two places with two
 * half-worked states, which is the mistake Admin\InboxController documents at
 * length and declines to make.
 *
 * `plan` becomes nullable in the same breath. The sheet deliberately does not
 * ask for one, and defaulting those rows to 'featured' would record a choice
 * the owner never made — then show it back to the specialist as though they
 * had. Null reads as "not chosen yet", which is the truth; the admin views
 * already print `$draft->plan?->label() ?? '—'` and ListingPublisher already
 * falls back through planTier().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_drafts', function (Blueprint $table) {
            $table->string('source', 32)->default('wizard')->index()->after('reference');
            $table->string('plan')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('listing_drafts', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
            $table->string('plan')->default('featured')->change();
        });
    }
};
