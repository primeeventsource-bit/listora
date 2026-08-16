<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\Seo\ExploreSeo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Listing::published()
            ->search($request->query('q'))
            ->kind($request->query('kind'))
            ->mode($request->query('mode'))
            ->region($request->query('region'))
            ->bedrooms($request->query('beds'))
            ->maxPrice($request->query('max'))
            ->sorted($request->query('sort'));

        $listings = $query->paginate(9)->withQueryString();

        $filters = [
            'q'      => $request->query('q'),
            'kind'   => $request->query('kind', 'all'),
            'mode'   => $request->query('mode', 'all'),
            'region' => $request->query('region', 'all'),
            'beds'   => $request->query('beds'),
            'max'    => $request->query('max'),
            'sort'   => $request->query('sort', 'recommended'),
        ];

        return view('pages.browse', [
            'listings' => $listings,
            'filters'  => $filters,
            // Explore is the paid-traffic landing page, so its title, canonical,
            // robots policy, and item payload are all derived per facet rather
            // than being one fixed set of tags. See ExploreSeo.
            'seo'      => new ExploreSeo($filters, $listings),
            'facets' => [
                'kind'   => Listing::published()->selectRaw('kind, count(*) c')->groupBy('kind')->pluck('c', 'kind'),
                'region' => Listing::published()->selectRaw('region, count(*) c')->groupBy('region')->orderBy('region')->pluck('c', 'region'),
                'mode'   => Listing::published()->selectRaw('mode, count(*) c')->groupBy('mode')->pluck('c', 'mode'),
            ],
        ]);
    }

    public function show(Listing $listing): View
    {
        $listing->increment('views');

        $similar = Listing::published()
            ->where('id', '!=', $listing->id)
            ->where(function ($q) use ($listing) {
                $q->where('region', $listing->region)->orWhere('kind', $listing->kind);
            })
            ->sorted(null)
            ->take(3)
            ->get();

        return view('pages.listing', compact('listing', 'similar'));
    }
}
