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

    /**
     * The figures on /about are counted, not asserted.
     *
     * This page kept "1,840 Resorts represented" and "100% Ownership verified"
     * after the home page stopped saying them. The first was a number nothing
     * in the system produced; the second stated a target as a measurement.
     * Both are representations to whoever reads the page, so both are derived
     * from the catalogue here and will move when it does - downwards included.
     */
    public function about(): View
    {
        $total = Listing::published()->count();

        return view('pages.about', [
            'total' => $total,
            'regions' => Listing::published()->distinct()->count('region'),
            'verified_pct' => $total > 0
                ? (int) round(Listing::published()->where('is_verified', true)->count() / $total * 100)
                : 0,
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
