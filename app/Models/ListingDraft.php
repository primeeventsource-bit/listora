<?php

namespace App\Models;

use App\Enums\DraftStatus;
use App\Enums\PlanTier;
use App\Support\Reference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What the "list your property" wizard produces, before it is a listing.
 *
 * A draft is deliberately its own table rather than a `listings` row in a
 * draft state. Anyone can submit one without an account, so these rows are
 * untrusted until a specialist verifies ownership — keeping them out of
 * `listings` means no query anywhere can accidentally surface unverified
 * content, rather than every query having to remember to exclude it.
 *
 * The draft is promoted to a real Listing by ListingPublisher once ownership
 * is verified. `listing_id` records what it became.
 */
class ListingDraft extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => DraftStatus::class,
            'plan' => PlanTier::class,
            'verified_at' => 'datetime',
            'declined_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $draft) {
            $draft->reference ??= Reference::generate(
                'D',
                fn (string $code) => static::query()->where('reference', $code)->exists(),
            );
        });
    }

    // ------------------------------------------------------------- relations

    /** Null when the wizard was completed by an anonymous visitor. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** The listing this draft became, once published. */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    // ---------------------------------------------------------------- scopes

    /** The review queue: everything a specialist still has to act on. */
    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereNotIn('status', [
            DraftStatus::Published->value,
            DraftStatus::Declined->value,
        ]);
    }

    public function scopeAwaitingVerification(Builder $q): Builder
    {
        return $q->whereIn('status', [
            DraftStatus::New->value,
            DraftStatus::PendingVerification->value,
        ]);
    }

    // ------------------------------------------------------------- behaviour

    /** Ownership confirmed — everything ListingPublisher needs to promote it. */
    public function isReadyToPublish(): bool
    {
        return $this->status === DraftStatus::Verified
            && $this->verified_at !== null;
    }

    public function planTier(): PlanTier
    {
        return $this->plan ?? PlanTier::Essential;
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }
}
