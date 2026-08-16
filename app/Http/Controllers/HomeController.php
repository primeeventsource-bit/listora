<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\Seo\HomeSeo;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $total = Listing::published()->count();

        return view('pages.home', [
            'featured' => Listing::published()->where('is_featured', true)
                ->sorted(null)->take(6)->get(),
            'recent'   => Listing::published()->sorted('newest')->take(4)->get(),
            // Identity rather than facets — the home page is one always-indexable
            // URL, so what it needs is Organization/WebSite data, not the
            // per-combination indexability logic Explore requires. See HomeSeo.
            'seo'      => new HomeSeo($total),
            'counts'   => [
                'total'  => $total,
                'home'   => Listing::published()->kind('home')->count(),
                'points' => Listing::published()->kind('points')->count(),
                'weeks'  => Listing::published()->kind('weeks')->count(),
            ],
            'covers'   => [
                'home'   => Listing::published()->kind('home')->inRandomOrder()->first(),
                'points' => Listing::published()->kind('points')->inRandomOrder()->first(),
                'weeks'  => Listing::published()->kind('weeks')->inRandomOrder()->first(),
            ],
        ]);
    }
}
