<?php

namespace App\Models;

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
        'is_featured'    => 'boolean',
        'is_verified'    => 'boolean',
        'bathrooms'      => 'float',
        'price'          => 'float',
        'available_from' => 'date',
        'available_to'   => 'date',
        'published_at'   => 'datetime',
    ];

    // ---------------------------------------------------------------- relations

    public function resort(): BelongsTo
    {
        return $this->belongsTo(Resort::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    // ------------------------------------------------------------------ scopes

    public function scopePublished(Builder $q): Builder
    {
        return $q->whereNotNull('published_at');
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
}
