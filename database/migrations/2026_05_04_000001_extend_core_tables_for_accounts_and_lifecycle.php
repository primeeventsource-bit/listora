<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings the four original Listora tables up to what the ported backend needs.
 *
 * Written as an additive migration rather than an edit to the 2026_01_01 files
 * because migrations are forward-only: a shipped migration is never modified,
 * a new one fixes it. That rule is what lets an environment that already ran
 * the originals reach this schema by running forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --- users: roles, name parts, lifecycle -------------------------
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('traveler')->after('email');

            // `name` stays the authoritative display value; the parts exist so
            // registration can collect them and greetings can use a first name.
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone', 40)->nullable()->after('email');

            // Deactivated users keep their rows and their roles — they simply
            // cannot act on them (see EnsurePermission).
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Denormalised from login_sessions so the admin user table sorts
            // without a join. login_sessions stays the audit source of truth.
            $table->timestamp('last_login_at')->nullable();

            $table->index('role');
        });

        // --- listings: ownership + lifecycle -----------------------------
        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('listing_draft_id')->nullable()->after('owner_id')->constrained('listing_drafts')->nullOnDelete();

            // `published_at` already exists and records when the term began.
            // `status` is the operator's decision and is a separate axis — see
            // Listing::scopePublished for why both are required to be public.
            $table->string('status', 24)->default('draft')->after('plan');

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // End of the paid advertising term. Drives ExpireListingTerms.
            $table->timestamp('expires_at')->nullable();

            $table->index(['status', 'published_at']);
            $table->index(['owner_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        // Existing seeded rows were public by virtue of published_at alone.
        // Carry them to the equivalent state under the new two-axis rule so
        // the browse page does not empty out on deploy.
        DB::table('listings')->whereNotNull('published_at')->update(['status' => 'active']);

        // --- listing_drafts: review pipeline -----------------------------
        Schema::table('listing_drafts', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('listing_id')->nullable()->after('owner_id')->constrained('listings')->nullOnDelete();

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decline_reason')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->index('status');
        });

        // --- inquiries: owner-side read state ----------------------------
        Schema::table('inquiries', function (Blueprint $table) {
            // The original `status` string carried sent/read/replied. Real
            // timestamps replace it: "when was this read" is a question the
            // owner dashboard and the evidence bundle both need answered.
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->index(['listing_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['listing_id', 'read_at']);
            $table->dropColumn(['read_at', 'replied_at', 'ip_address']);
        });

        Schema::table('listing_drafts', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['listing_id']);
            $table->dropForeign(['verified_by_user_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'owner_id', 'listing_id', 'verified_at', 'verified_by_user_id',
                'decline_reason', 'declined_at', 'published_at',
            ]);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['listing_draft_id']);
            $table->dropForeign(['verified_by_user_id']);
            $table->dropIndex(['status', 'published_at']);
            $table->dropIndex(['owner_id', 'status']);
            $table->dropIndex(['status', 'expires_at']);
            $table->dropColumn([
                'owner_id', 'listing_draft_id', 'status',
                'verified_at', 'verified_by_user_id', 'expires_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['deactivated_by_user_id']);
            $table->dropForeign(['created_by_user_id']);
            $table->dropIndex(['role']);
            $table->dropColumn([
                'role', 'first_name', 'last_name', 'phone',
                'deactivated_at', 'deactivated_by_user_id',
                'created_by_user_id', 'last_login_at',
            ]);
        });
    }
};
