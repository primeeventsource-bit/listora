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
}
