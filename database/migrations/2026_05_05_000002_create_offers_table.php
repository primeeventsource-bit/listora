<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer offers and inquiries on a listing.
 *
 * `owner_user_id` is denormalised from listings.owner_id so the owner's queue
 * is a single-table read, but authorisation never trusts it — Offer::
 * scopeForListingsOwnedBy joins through `listings` so a listing that changes
 * hands cannot leave an offer readable by its previous owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();       // LST-F-8F2A1B

            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            // Null for an offer submitted by an anonymous visitor.
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('kind', 16)->default('inquiry');  // offer | inquiry
            $table->string('status', 16)->default('active');

            // Contact details as given at submission time. Kept alongside the
            // buyer_user_id rather than read through it: a signed-in buyer may
            // name a different contact, and the record must show what was
            // actually sent to the owner.
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('message');

            // Integer cents, null on a plain inquiry.
            $table->unsignedBigInteger('offer_amount_cents')->nullable();

            $table->date('arrive')->nullable();
            $table->date('depart')->nullable();
            $table->unsignedTinyInteger('guests')->nullable();

            // Chargeback evidence: proves who submitted from where.
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['listing_id', 'status']);
            $table->index(['owner_user_id', 'status']);
            // Drives the ExpireOffers sweep.
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
