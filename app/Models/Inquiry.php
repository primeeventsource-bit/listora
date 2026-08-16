<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message sent from a traveler to a listing owner.
 *
 * Distinct from Offer: an inquiry is free-text with no price, no expiry, and
 * no accept/decline decision attached. Listora forwards it and steps out of
 * the way — the reply goes straight to the traveler's own inbox.
 */
class Inquiry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'arrive' => 'date',
            'depart' => 'date',
            'read_at' => 'datetime',
            'replied_at' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->whereNull('read_at');
    }

    public function scopeForListingsOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->whereHas('listing', fn (Builder $l) => $l->where('owner_id', $userId));
    }
}
