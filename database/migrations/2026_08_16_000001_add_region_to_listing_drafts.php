<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `listing_drafts` was missing `region`.
 *
 * The wizard's form request validates it, the wizard's form collects it, and
 * ListingPublisher reads it when promoting a draft into a listing — but the
 * column never existed, so every submission that included a region failed on
 * insert. Caught by AllRoutesRenderTest exercising the draft factory.
 *
 * Region matters on the listing side: it is what browse filters on and what
 * the Premier plan's "top-of-results placement in your region" is scoped by.
 * A draft that loses it forces a reviewer to re-derive it from the city.
 *
 * Added as a new migration rather than edited into 2026_05_04: migrations are
 * forward-only, and that one has shipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_drafts', function (Blueprint $table) {
            $table->string('region', 96)->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('listing_drafts', function (Blueprint $table) {
            $table->dropColumn('region');
        });
    }
};
