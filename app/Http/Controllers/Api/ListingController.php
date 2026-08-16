<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Public listing search and detail.
 *
 * No auth: browsing without an account is the product, and requiring a token
 * to read a listing would put a wall in front of the one thing the site is
 * for. Every query starts from scopePublished, so an unpublished, paused, or
 * expired listing is unreachable here regardless of what is asked for.
 */
class ListingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'kind' => ['nullable', 'string', 'max:16'],
            'mode' => ['nullable', 'string', 'max:16'],
            'region' => ['nullable', 'string', 'max:96'],
            'beds' => ['nullable', 'integer', 'min:0', 'max:20'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', 'string', 'max:24'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $listings = Listing::published()
            ->search($validated['q'] ?? null)
            ->kind($validated['kind'] ?? null)
            ->mode($validated['mode'] ?? null)
            ->region($validated['region'] ?? null)
            ->bedrooms($validated['beds'] ?? null)
            ->maxPrice($validated['max_price'] ?? null)
            ->sorted($validated['sort'] ?? null)
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return ListingResource::collection($listings);
    }

    public function show(Listing $listing): ListingResource
    {
        abort_unless($listing->isPubliclyVisible(), 404);

        // Counted here as well as on the web route: an app browsing through
        // the API is a real view, and leaving it out would make the owner's
        // performance numbers quietly wrong.
        $listing->increment('views');

        return new ListingResource($listing);
    }
}
