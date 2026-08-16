<?php

namespace App\Models;

use App\Enums\OfferKind;
use App\Enums\OfferStatus;
use App\Support\Reference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A traveler's inquiry or priced offer on a listing.
 *
 * Listora is not a party to whatever the two sides agree: accepting an offer
 * exchanges contact details and closes the record, it does not reserve dates
 * or move money. The row survives because it is the evidence of what was
 * asked and answered — the chargeback bundle reads it, and support quotes the
 * reference back to callers.
 *
 * Offers expire on a fixed clock so an owner's silence resolves to something
 * definite rather than leaving a buyer waiting indefinitely.
 */
class Offer extends Model
{
    use HasFactory;

    /** Hours an open offer stays actionable before ExpireOffers closes it. */
    public const EXPIRY_HOURS = 72;

    protected $fillable = [
        'reference',
        'listing_id',
        'buyer_user_id',
        'owner_user_id',
        'kind',
        'status',
        'name',
        'email',
        'phone',
        'message',
        'offer_amount_cents',
        'arrive',
        'depart',
        'guests',
        'ip_address',
        'responded_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => OfferKind::class,
            'status' => OfferStatus::class,
            'offer_amount_cents' => 'integer',
            'arrive' => 'date',
            'depart' => 'date',
            'responded_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $offer) {
            $offer->reference ??= Reference::generate(
                'F',
                fn (string $code) => static::query()->where('reference', $code)->exists(),
            );

            $offer->expires_at ??= now()->addHours(self::EXPIRY_HOURS);
        });
    }

    // ------------------------------------------------------------- relations

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /** Null for offers submitted by an anonymous visitor. */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    // ---------------------------------------------------------------- scopes

    public function scopeOpen(Builder $q): Builder
    {
        return $q->where('status', OfferStatus::Active->value);
    }

    /** Open offers whose clock has run out — what ExpireOffers sweeps. */
    public function scopeLapsed(Builder $q): Builder
    {
        return $q->open()->where('expires_at', '<=', now());
    }

    /**
     * Offers on listings owned by a given user.
     *
     * Scoped through `listings.owner_id` rather than trusting the denormalised
     * `owner_user_id` column, so a listing that changes hands cannot leave an
     * offer visible to its previous owner.
     */
    public function scopeForListingsOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->whereHas('listing', fn (Builder $l) => $l->where('owner_id', $userId));
    }

    // ------------------------------------------------------------- behaviour

    public function isActionable(): bool
    {
        return $this->status->isActionable() && ! $this->hasLapsed();
    }

    public function hasLapsed(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getAmountFormattedAttribute(): ?string
    {
        return $this->offer_amount_cents === null
            ? null
            : '$'.number_format($this->offer_amount_cents / 100, 2);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
