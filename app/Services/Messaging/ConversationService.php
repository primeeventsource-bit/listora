<?php

namespace App\Services\Messaging;

use App\Models\Conversation;
use App\Models\Listing;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Starting and continuing conversations.
 *
 * Both entry points are here rather than in the controllers because a
 * conversation is created from three places - an inquiry, an offer, and a
 * reply - and the invariants are the same each time: one thread per listing
 * per visitor, last_message_at kept current, and the sender's own read mark
 * moved so their message does not come back to them as unread.
 */
class ConversationService
{
    /**
     * Find or start the thread between a listing's owner and one visitor.
     *
     * Returns null when the listing has no owner. Ownerless listings exist -
     * every seeded one is - and they have nobody to carry the other side of a
     * conversation, so there is no thread to make rather than a broken one.
     */
    public function between(Listing $listing, User $visitor, string $startedFrom = Conversation::FROM_INQUIRY): ?Conversation
    {
        if (! $listing->owner_id) {
            return null;
        }

        // An owner inquiring on their own listing would create a thread with
        // themselves on both sides, which then renders as a conversation with
        // no counterpart.
        if ($listing->owner_id === $visitor->id) {
            return null;
        }

        return Conversation::firstOrCreate(
            [
                'listing_id' => $listing->id,
                'visitor_user_id' => $visitor->id,
            ],
            [
                'owner_user_id' => $listing->owner_id,
                'started_from' => $startedFrom,
            ],
        );
    }

    /**
     * Post a message, and keep the thread's summary fields honest.
     *
     * In a transaction: a message written without last_message_at moving would
     * sit at the bottom of the other party's inbox and be missed, which for a
     * platform whose entire job is passing messages along is the worst
     * possible partial write.
     */
    public function post(Conversation $conversation, User $sender, string $body): Message
    {
        return DB::transaction(function () use ($conversation, $sender, $body) {
            $message = $conversation->messages()->create([
                'sender_user_id' => $sender->id,
                'body' => $body,
            ]);

            $now = now();

            // The sender has, by definition, read their own message.
            $senderReadColumn = $sender->id === $conversation->owner_user_id
                ? 'owner_read_at'
                : 'visitor_read_at';

            $conversation->forceFill([
                'last_message_at' => $now,
                $senderReadColumn => $now,
            ])->save();

            return $message;
        });
    }

    /** Threads with something the user has not seen. Used for the nav badge. */
    public function unreadCountFor(User $user): int
    {
        return Conversation::query()
            ->forUser($user->id)
            ->whereNotNull('last_message_at')
            ->where(function ($q) use ($user) {
                $q->where(function ($owner) use ($user) {
                    $owner->where('owner_user_id', $user->id)
                        ->where(fn ($w) => $w->whereNull('owner_read_at')
                            ->orWhereColumn('owner_read_at', '<', 'last_message_at'));
                })->orWhere(function ($visitor) use ($user) {
                    $visitor->where('visitor_user_id', $user->id)
                        ->where(fn ($w) => $w->whereNull('visitor_read_at')
                            ->orWhereColumn('visitor_read_at', '<', 'last_message_at'));
                });
            })
            ->count();
    }
}
