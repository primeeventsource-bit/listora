<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The property's address, on the intake sheet.
 *
 * Deliberately on `listing_drafts` and not on `listings`. A draft is the
 * private intake record a specialist works from, and a street address is one
 * of the things that makes verifying ownership possible. A published listing
 * shows the city and state only - an advertiser is inviting inquiries, not
 * telling the internet which house is empty.
 *
 * ListingPublisher copies named columns across, so nothing here reaches a
 * public page unless someone adds it there on purpose.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_drafts', function (Blueprint $table) {
            $table->string('address', 200)->nullable()->after('resort_name');
        });
    }

    public function down(): void
    {
        Schema::table('listing_drafts', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
