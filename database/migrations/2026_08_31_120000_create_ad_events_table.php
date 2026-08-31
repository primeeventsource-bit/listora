<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advertising analytics events.
 *
 * Deliberately separate from tracking_events. That table is a tamper-evident
 * audit log: every insert reads the previous row's current_hash and chains
 * onto it, and TrackingEvent::verify() walks the chain looking for breaks. It
 * is the right shape for security evidence and the wrong shape for traffic -
 * two visitors arriving in the same instant would both chain onto the same
 * parent and the log would report itself as tampered with, from ordinary
 * concurrent traffic rather than an attack.
 *
 * This table takes the volume instead: plain inserts, no chain, indexed for
 * aggregation. tracking_events keeps its integrity role, so "here is proof the
 * advertisement ran" and "here are the numbers" stay separate claims backed by
 * the right storage for each.
 *
 * Privacy is enforced by column rather than by convention. ip_address is
 * admin-only and never selected for a member-facing query; ip_hash exists so
 * unique visitors can be counted without reading an address. Geography is
 * recorded as approximate by definition - it is derived from IP and must never
 * be presented as where somebody physically is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();

            // What was looked at. ad_number and listing_ref are denormalized
            // on purpose: reports group by them constantly, and a listing that
            // is later deleted should not erase the evidence that its
            // advertising ran.
            $table->string('ad_number', 24)->nullable();
            $table->string('listing_ref', 32)->nullable();
            $table->foreignId('member_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();

            // Where in the funnel. Impression -> listing view -> details ->
            // inquiry/offer click -> account -> submitted -> message started.
            $table->string('event_type', 32);

            $table->string('url', 512)->nullable();
            $table->string('path', 255)->nullable();

            // Attribution.
            $table->string('referrer', 512)->nullable();
            $table->string('referrer_host', 255)->nullable();
            $table->string('utm_source', 128)->nullable();
            $table->string('utm_medium', 128)->nullable();
            $table->string('utm_campaign', 191)->nullable();
            $table->string('utm_term', 191)->nullable();
            $table->string('utm_content', 191)->nullable();
            $table->string('click_id', 191)->nullable();
            // google_ads | meta | instagram | email | referral | organic | direct | other
            $table->string('source_category', 24)->default('direct');

            // Who, without saying who. visitor_id is the first-party cookie
            // that already exists; session_id groups one sitting.
            $table->string('visitor_id', 36)->nullable();
            $table->string('session_id', 36)->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            // ADMIN ONLY. Never selected by a member-facing query.
            $table->string('ip_address', 45)->nullable();
            // Counts unique visitors without reading an address.
            $table->char('ip_hash', 64)->nullable();

            // Approximate, from IP. Never an exact position.
            $table->string('geo_city', 128)->nullable();
            $table->string('geo_region', 128)->nullable();
            $table->string('geo_country', 2)->nullable();
            $table->decimal('geo_lat', 9, 6)->nullable();
            $table->decimal('geo_lng', 9, 6)->nullable();

            // mobile | tablet | desktop | bot | unknown
            $table->string('device_category', 16)->default('unknown');
            $table->string('browser', 48)->nullable();
            $table->string('os', 48)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->timestamp('occurred_at')->useCurrent();

            // Reporting reads this table by advertiser over a window far more
            // than by anything else, so the composites lead with the owner.
            $table->index(['ad_number', 'occurred_at']);
            $table->index(['member_user_id', 'occurred_at']);
            $table->index(['listing_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
            $table->index(['source_category', 'occurred_at']);
            $table->index('occurred_at');
            $table->index('visitor_id');
            $table->index('session_id');
            $table->index('ip_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_events');
    }
};
