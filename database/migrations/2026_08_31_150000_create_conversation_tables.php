<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peer-to-peer conversations between an advertiser and an interested visitor.
 *
 * Listora carries the messages and stays out of them. A conversation always
 * belongs to a listing, because the thing being discussed is an advertisement
 * - a message with no listing would be a direct-message system, which is a
 * different product with different abuse problems.
 *
 * Both sides are accounts, never an email address. An inquiry can currently be
 * sent by a guest, and a guest has nowhere to receive a reply and no identity
 * to attach one to, so a conversation only exists once both parties have an
 * account. That matches the brief's step 2 rather than working around it.
 *
 * One conversation per listing per visitor. A visitor who inquires twice about
 * the same property is continuing a conversation, not starting a second one -
 * and two threads about one listing means an advertiser answering in the wrong
 * place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();

            // Named by role rather than "user_one"/"user_two": every query
            // here cares which side is which, and a numbered pair would push
            // that decision into each of them.
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visitor_user_id')->constrained('users')->cascadeOnDelete();

            // Denormalised so an inbox can sort without touching messages.
            $table->timestamp('last_message_at')->nullable();

            // Per-side read marks. Unread is derived by comparing these with
            // last_message_at, so nothing has to be written to every message
            // row when a thread is opened.
            $table->timestamp('owner_read_at')->nullable();
            $table->timestamp('visitor_read_at')->nullable();

            // Where the conversation came from, for reporting the funnel.
            $table->string('started_from', 16)->default('inquiry');

            $table->timestamps();

            $table->unique(['listing_id', 'visitor_user_id']);
            $table->index(['owner_user_id', 'last_message_at']);
            $table->index(['visitor_user_id', 'last_message_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
