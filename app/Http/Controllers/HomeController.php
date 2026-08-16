<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('pages.home', [
            'featured' => Listing::published()->where('is_featured', true)
                ->sorted(null)->take(6)->get(),
            'recent'   => Listing::published()->sorted('newest')->take(4)->get(),
            'counts'   => [
                'total'  => Listing::published()->count(),
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
