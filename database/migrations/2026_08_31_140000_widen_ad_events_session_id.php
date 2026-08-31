<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * session_id was varchar(36); Laravel session ids are 40 characters.
 *
 * Sized as a UUID by assumption, because visitor_id beside it genuinely is
 * one. Laravel's session id is Str::random(40) instead, so every insert
 * failed on MySQL in strict mode - and the recorder swallows its own failures
 * by design, so the page kept serving 200 and the table simply stayed empty.
 *
 * The test suite could not have caught this. It runs on SQLite, which ignores
 * varchar lengths entirely, so the same insert succeeds locally and in CI and
 * fails only against MySQL. Widened to 64 rather than 40 so a future change to
 * session id length does not reopen it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_events', function (Blueprint $table) {
            $table->string('session_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ad_events', function (Blueprint $table) {
            $table->string('session_id', 36)->nullable()->change();
        });
    }
};
