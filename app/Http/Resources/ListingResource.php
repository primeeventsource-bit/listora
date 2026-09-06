<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public shape of a listing.
 *
 * `owner_id`, `owner_name`'s contact details, verification actor, and the
 * internal reference are all absent. A listing is public, but who advertises
 * it and who signed off on it are not — that data exists for operations and
 * for the owner, and an API resource is exactly where it would leak by
 * accident.
 */
class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'kind' => $this->kind,
            'kind_label' => $this->kind_label,
            'mode' => $this->mode,
            'title' => $this->title,
            'headline' => $this->headline,
            'description' => $this->when($request->routeIs('*.show'), $this->description),

            'location' => [
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'region' => $this->region,
                'property_name' => $this->property_name,
                'club_name' => $this->club_name,
            ],

            'unit' => [
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'sleeps' => $this->sleeps,
                'points' => $this->points,
                'week_number' => $this->week_number,
                'season' => $this->season,
                'key_fact' => $this->key_fact,
            ],

            // The owner's asking price — what they want for the property.
            // Listora never charges it and is not party to its settlement.
            'asking_price' => [
                'amount' => (float) $this->price,
                'unit' => $this->price_unit,
                'unit_label' => $this->price_unit_label,
                'formatted' => $this->price_formatted,
                'currency' => $this->currency,
            ],

            'amenities' => $this->amenities ?: [],
            'photos' => $this->visiblePhotos(),
            'is_featured' => (bool) $this->is_featured,
            'is_verified' => (bool) $this->is_verified,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
