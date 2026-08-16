<?php

namespace App\Services\Listings;

use App\Models\Listing;
use App\Support\Reference;

/**
 * Produces the unique, stable URL slug a listing is addressed by.
 *
 * Extracted from the publisher so the uniqueness check has one home: slugs are
 * the listing's route key, and two listings resolving to the same URL would
 * make one of them permanently unreachable.
 */
class SlugFactory
{
    public function uniqueFor(string $title): string
    {
        return Reference::uniqueSlug(
            $title,
            fn (string $slug) => Listing::query()->where('slug', $slug)->exists(),
        );
    }
}
