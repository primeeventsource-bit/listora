<?php

namespace App\Models;

use App\Enums\AdEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One advertising event.
 *
 * The privacy split is expressed here rather than left to whoever writes the
 * next query. `scopeForMember()` is the only entry point a member-facing
 * screen should use, and it excludes ip_address at the SQL level - so an
 * advertiser's dashboard cannot leak a visitor's address even if a later view
 * renders every column it is given.
 *
 * Geography is approximate by construction. It comes from an IP lookup, which
 * places a visitor somewhere near their network's registered location and
 * routinely lands a suburb or a whole city away. Anything rendering these
 * columns must say "approximate" beside them.
 */
class AdEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'event_uuid', 'ad_number', 'listing_ref', 'member_user_id', 'listing_id',
        'event_type', 'url', 'path', 'referrer', 'referrer_host',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'click_id', 'source_category', 'visitor_id', 'session_id', 'actor_user_id',
        'ip_address', 'ip_hash', 'geo_city', 'geo_region', 'geo_country',
        'geo_lat', 'geo_lng', 'device_category', 'browser', 'os', 'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'event_type' => AdEventType::class,
        'geo_lat' => 'float',
        'geo_lng' => 'float',
        'occurred_at' => 'datetime',
    ];

    /**
     * Columns a member is allowed to see.
     *
     * ip_address is absent on purpose and must stay absent. The advertiser
     * gets the geography, which is what makes the analytics useful, and not
     * the address, which is the visitor's.
     */
    public const MEMBER_VISIBLE = [
        'id', 'ad_number', 'listing_ref', 'listing_id', 'event_type',
        'referrer_host', 'utm_source', 'utm_medium', 'utm_campaign', 'source_category',
        'geo_city', 'geo_region', 'geo_country', 'geo_lat', 'geo_lng',
        'device_category', 'browser', 'os', 'visitor_id', 'session_id', 'occurred_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (AdEvent $event): void {
            if (empty($event->event_uuid)) {
                $event->event_uuid = (string) Str::uuid();
            }

            if (empty($event->occurred_at)) {
                $event->occurred_at = now();
            }
        });
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    // ------------------------------------------------------------------ scopes

    /**
     * The only query a member-facing screen should build from.
     *
     * Scoped to the advertiser's own events and stripped of the address
     * column, so the restriction survives a view that later renders whatever
     * it is handed.
     */
    public function scopeForMember(Builder $q, int $memberUserId): Builder
    {
        return $q->where('member_user_id', $memberUserId)
            ->select(self::MEMBER_VISIBLE);
    }

    public function scopeBetween(Builder $q, $from, $to): Builder
    {
        return $q->whereBetween('occurred_at', [$from, $to]);
    }

    public function scopeOfType(Builder $q, AdEventType|array $types): Builder
    {
        $values = collect(is_array($types) ? $types : [$types])
            ->map(fn ($t) => $t instanceof AdEventType ? $t->value : $t)
            ->all();

        return $q->whereIn('event_type', $values);
    }

    /** Rows that carry a usable coordinate, which is all a map can plot. */
    public function scopeLocated(Builder $q): Builder
    {
        return $q->whereNotNull('geo_lat')->whereNotNull('geo_lng');
    }
}
