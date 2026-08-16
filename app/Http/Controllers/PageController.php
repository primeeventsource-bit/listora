<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function how(): View
    {
        return view('pages.how');
    }

    public function pricing(): View
    {
        return view('pages.pricing', ['plans' => config('listora.plans')]);
    }

    public function about(): View
    {
        return view('pages.about', [
            'total' => Listing::published()->count(),
        ]);
    }

    public function apps(): View
    {
        return view('pages.apps', [
            'sample' => Listing::published()->where('is_featured', true)->take(3)->get(),
        ]);
    }

    /**
     * The inventory register — the ten most recently published listings, given
     * as facts rather than as photographs.
     *
     * Explore (/browse) is the search surface: filters, photo cards, paging,
     * and you arrive knowing roughly what you want. This is deliberately the
     * other thing — one dense table read top to bottom, so what is on the
     * books right now can be taken in at a glance. Ten rows is the point; a
     * register long enough to need paging is just Explore with worse photos.
     *
     * Newest first, not the `recommended` order browse uses. Placement is a
     * paid-plan concern and has no business deciding what a register shows.
     */
    public function inventory(): View
    {
        $published = fn () => Listing::published();

        return view('pages.inventory', [
            'listings' => $published()->sorted('newest')->take(10)->get(),
            'total'    => $published()->count(),
            'byKind'   => $published()->selectRaw('kind, count(*) c')->groupBy('kind')->pluck('c', 'kind'),
            'regions'  => $published()->distinct()->count('region'),
        ]);
    }
}
