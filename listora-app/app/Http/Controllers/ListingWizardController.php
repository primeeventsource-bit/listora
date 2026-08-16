<?php

namespace App\Http\Controllers;

use App\Models\ListingDraft;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListingWizardController extends Controller
{
    public function create(): View
    {
        return view('pages.list', ['plans' => config('listora.plans')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kind'        => ['required', 'in:home,points,weeks'],
            'mode'        => ['required', 'in:rent,own'],
            'owner_name'  => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:190'],
            'phone'       => ['nullable', 'string', 'max:40'],
            'resort_name' => ['nullable', 'string', 'max:160'],
            'club_name'   => ['nullable', 'string', 'max:160'],
            'city'        => ['nullable', 'string', 'max:120'],
            'state'       => ['nullable', 'string', 'max:64'],
            'title'       => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:6000'],
            'bedrooms'    => ['nullable', 'integer', 'min:0', 'max:20'],
            'sleeps'      => ['nullable', 'integer', 'min:1', 'max:40'],
            'points'      => ['nullable', 'integer', 'min:1', 'max:500000'],
            'week_number' => ['nullable', 'integer', 'min:1', 'max:53'],
            'season'      => ['nullable', 'string', 'max:60'],
            'price'       => ['nullable', 'integer', 'min:0'],
            'price_unit'  => ['nullable', 'in:total,night,week,point'],
            'plan'        => ['required', 'in:essential,featured,premier'],
        ]);

        $data['reference'] = 'DRF-'.strtoupper(substr(md5(uniqid('', true)), 0, 6));

        $draft = ListingDraft::create($data);

        return redirect()
            ->route('list.create')
            ->with('draft', $draft->reference)
            ->with('plan', config("listora.plans.{$draft->plan}.name"));
    }
}
