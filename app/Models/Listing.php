<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Enums\PlanTier;
use App\Support\AdNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Listing extends Model
{
    use HasFactory;

    public const KIND_HOME   = 'home';
    public const KIND_POINTS = 'points';
    public const KIND_WEEKS  = 'weeks';

    /** Category labels. Note: Listora never uses the industry's legacy term. */
    public const KINDS = [
        self::KIND_HOME   => 'Vacation Properties',
        self::KIND_POINTS => 'Resort Club Points',
        self::KIND_WEEKS  => 'Vacation Weeks',
    ];

    public const KINDS_SHORT = [
        self::KIND_HOME   => 'Vacation Property',
        self::KIND_POINTS => 'Club Points',
        self::KIND_WEEKS  => 'Vacation Week',
    ];

    public const MODES = [
        'rent' => 'Available to Rent',
        'own'  => 'Available to Own',
    ];

    protected $guarded = [];

    protected $casts = [
        'amenities'      => 'array',
        'photos'         => 'array',
        'status'         => ListingStatus::class,
        'plan'           => PlanTier::class,
        'is_featured'    => 'boolean',
        'is_verified'    => 'boolean',
        'bathrooms'      => 'float',
        'price'          => 'float',
        'available_from' => 'date',
        'available_to'   => 'date',
        'verified_at'    => 'datetime',
        'published_at'   => 'datetime',
        'expires_at'     => 'datetime',
    ];

    // ---------------------------------------------------------------- relations

    public function resort(): BelongsTo
    {
        return $this->belongsTo(Resort::class);
    }

    /** The account that advertises this listing. Null for seeded demo rows. */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(ListingDraft::class);
    }

    // ------------------------------------------------------------------ scopes

    /**
     * Publicly visible listings.
     *
     * Both conditions are required and neither implies the other: `status`
     * is the operator's decision and `published_at` is the moment the paid
     * term began. A listing whose order lapsed goes Expired while keeping
     * its published_at, which is how renewal restores it without re-dating.
     */
    /**
     * Everything the public may see.
     *
     * The category restriction lives here rather than in each controller
     * because `published()` is the single gate every public surface goes
     * through - browse, the home page, the inventory register, the advertising
     * URLs, structured data, and the API. Filtering at each call site would
     * mean one forgotten query publishing exactly what this is meant to
     * withhold.
     *
     * Points and vacation-week categories are hidden while
     * `timeshare_categories` is off. This is a payment-underwriting posture,
     * not a product decision: those categories are assessed as high risk, and
     * the flag exists so they can be turned back on in Settings the moment
     * that is settled, without a deploy.
     *
     * Fails CLOSED - note the `false` default, the opposite of the convention
     * elsewhere in this codebase. A missing flag row must hide the categories,
     * because the cost of wrongly showing them is a failed underwriting review
     * and the cost of wrongly hiding them is a switch someone flips back on.
     */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', ListingStatus::Active->value)
            ->whereNotNull('published_at')
            ->unless(
                feature('timeshare_categories', null, false),
                fn (Builder $w) => $w->where('kind', self::KIND_HOME),
            );
    }

    public function scopeKind(Builder $q, ?string $kind): Builder
    {
        return $kind && $kind !== 'all' ? $q->where('kind', $kind) : $q;
    }

    public function scopeMode(Builder $q, ?string $mode): Builder
    {
        return $mode && $mode !== 'all' ? $q->where('mode', $mode) : $q;
    }

    public function scopeRegion(Builder $q, ?string $region): Builder
    {
        return $region && $region !== 'all' ? $q->where('region', $region) : $q;
    }

    public function scopeBedrooms(Builder $q, $min): Builder
    {
        return $min ? $q->where('bedrooms', '>=', (int) $min) : $q;
    }

    public function scopeMaxPrice(Builder $q, $max): Builder
    {
        return $max ? $q->where('price', '<=', (int) $max) : $q;
    }

    public function scopeOwnedBy(Builder $q, int $userId): Builder
    {
        return $q->where('owner_id', $userId);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }

        $t = '%'.str_replace('%', '', trim($term)).'%';

        return $q->where(function (Builder $w) use ($t) {
            $w->where('title', 'like', $t)
              ->orWhere('resort_name', 'like', $t)
              ->orWhere('club_name', 'like', $t)
              ->orWhere('city', 'like', $t)
              ->orWhere('state', 'like', $t)
              ->orWhere('region', 'like', $t)
              ->orWhere('country', 'like', $t);
        });
    }

    public function scopeSorted(Builder $q, ?string $sort): Builder
    {
        return match ($sort) {
            'price_low'  => $q->orderBy('price'),
            'price_high' => $q->orderByDesc('price'),
            'newest'     => $q->orderByDesc('published_at'),
            'bedrooms'   => $q->orderByDesc('bedrooms'),
            default      => $q->orderByDesc('is_featured')
                              ->orderByRaw("CASE plan WHEN 'premier' THEN 0 WHEN 'featured' THEN 1 ELSE 2 END")
                              ->orderByDesc('views'),
        };
    }

    // -------------------------------------------------------------- behaviour

    /** Has the advertising term run out? */
    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status?->isPublic() && $this->published_at !== null;
    }

    /** Photos beyond the plan's allowance are kept but never rendered. */
    public function visiblePhotos(): array
    {
        $limit = $this->plan?->photoLimit() ?? PlanTier::Essential->photoLimit();

        return array_slice($this->photos ?: [], 0, $limit);
    }

    // -------------------------------------------------------------- accessors

    public function getKindLabelAttribute(): string
    {
        return self::KINDS_SHORT[$this->kind] ?? 'Listing';
    }

    public function getModeLabelAttribute(): string
    {
        return $this->mode === 'rent' ? 'Rent' : 'Own';
    }

    public function getLocationAttribute(): string
    {
        return collect([$this->city, $this->state ?: $this->country])
            ->filter()->implode(', ');
    }

    public function getCoverAttribute(): string
    {
        return $this->photoAt(0);
    }

    public function photoAt(int $i): string
    {
        $photos = $this->photos ?: [];

        return $photos[$i] ?? ($photos[0] ?? '');
    }

    /** Sized Unsplash delivery URL — swap this one method to move to your own CDN. */
    public function photoUrl(int $i = 0, int $w = 1200, int $h = 900): string
    {
        $base = $this->photoAt($i);

        if (! $base) {
            return '';
        }

        return $base.'?auto=format&fit=crop&w='.$w.'&h='.$h.'&q=75';
    }

    public function getPriceFormattedAttribute(): string
    {
        return $this->price_unit === 'point'
            ? '$'.number_format((float) $this->price, 2)
            : '$'.number_format((float) $this->price);
    }

    /** What a club-points listing costs in total, not just per point. */
    public function getTotalPriceAttribute(): ?float
    {
        return $this->price_unit === 'point' && $this->points
            ? round((float) $this->price * $this->points)
            : null;
    }

    public function getPriceUnitLabelAttribute(): string
    {
        return match ($this->price_unit) {
            'night' => '/ night',
            'week'  => '/ week',
            'point' => '/ point',
            default => $this->mode === 'own' ? '' : 'total',
        };
    }

    /** The one-line fact that matters most for this category. */
    public function getKeyFactAttribute(): string
    {
        return match ($this->kind) {
            self::KIND_POINTS => number_format($this->points).' club points',
            self::KIND_WEEKS  => 'Week '.$this->week_number.($this->season ? ' · '.$this->season : ''),
            default           => $this->bedrooms.' bed · '.rtrim(rtrim(number_format($this->bathrooms, 1), '0'), '.').' bath',
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Where this listing publicly lives.
     *
     * One method rather than route('listings.show', $listing) at ten call
     * sites: that route is now a permanent redirect to the canonical
     * advertising URL, so every internal link through it would cost visitors
     * an extra round trip and hand search engines a redirect where a direct
     * link belongs.
     *
     * Falls back to the legacy address when either number is missing, so a
     * row that predates the columns still links somewhere real rather than
     * throwing while building a URL.
     */
    public function publicUrl(): string
    {
        $ownerNumber = $this->relationLoaded('owner')
            ? $this->owner?->ad_number
            : $this->owner()->value('ad_number');

        if (! $ownerNumber || ! $this->ad_number) {
            return route('listings.show', $this);
        }

        return route('ad.show', [
            'adNumber' => $ownerNumber,
            'listingNumber' => $this->ad_number,
        ]);
    }

    /**
     * Every listing gets its own advertising number at creation.
     *
     * Separate from the advertiser's member number: the member number says
     * whose advertising account this is and outlives any one property, the
     * listing number says which property. A public advertising URL carries
     * both, so a row in a report identifies the advertiser and the listing
     * without a join.
     */
    protected static function booted(): void
    {
        static::creating(function (Listing $listing): void {
            if (empty($listing->ad_number)) {
                $listing->ad_number = AdNumber::for(static::class);
            }
        });
    }
}
