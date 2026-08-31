<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One conversation about one listing, between its advertiser and one visitor.
 *
 * Access is expressed here rather than in each controller. scopeForUser() is
 * the only way a screen should reach conversations, and includes() is what
 * authorises a single thread - a thread is private to exactly two people, and
 * getting that wrong means showing a stranger somebody's negotiation.
 */
class Conversation extends Model
{
    use HasFactory;

    public const FROM_INQUIRY = 'inquiry';

    public const FROM_OFFER = 'offer';

    protected $fillable = [
        'listing_id', 'owner_user_id', 'visitor_user_id',
        'last_message_at', 'owner_read_at', 'visitor_read_at', 'started_from',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'owner_read_at' => 'datetime',
        'visitor_read_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visitor_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    // ------------------------------------------------------------ membership

    /** Conversations this user is a party to, either side. */
    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where(fn ($w) => $w->where('owner_user_id', $userId)
            ->orWhere('visitor_user_id', $userId));
    }

    public function includes(?User $user): bool
    {
        return $user !== null
            && in_array($user->id, [$this->owner_user_id, $this->visitor_user_id], true);
    }

    /** The other party, from this user's point of view. */
    public function counterpartFor(User $user): ?User
    {
        return $user->id === $this->owner_user_id ? $this->visitor : $this->owner;
    }

    /**
     * Has this user not yet seen the latest message?
     *
     * Compares their read mark with last_message_at rather than tracking a
     * per-message read state - the inbox only needs "is there something new",
     * and a per-message table would be written on every thread open.
     */
    public function isUnreadFor(User $user): bool
    {
        if (! $this->last_message_at) {
            return false;
        }

        $readAt = $user->id === $this->owner_user_id
            ? $this->owner_read_at
            : $this->visitor_read_at;

        return $readAt === null || $readAt->lt($this->last_message_at);
    }

    public function markReadFor(User $user): void
    {
        $column = $user->id === $this->owner_user_id ? 'owner_read_at' : 'visitor_read_at';

        $this->forceFill([$column => now()])->save();
    }
}
