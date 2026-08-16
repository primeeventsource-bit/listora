<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OfferStatus;
use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Cross-platform register of every inquiry and offer.
 *
 * Read-only by design. Responding is the listing owner's decision and lives on
 * their dashboard; an admin screen with accept/decline buttons would let staff
 * answer on an owner's behalf for an arrangement Listora is not party to.
 * `offers.respond` exists in the catalog for the support case where that is
 * genuinely needed, and it is deliberately not wired to this screen.
 */
class OfferController extends Controller
{
    public function index(Request $request): View
    {
        $offers = Offer::query()
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('q'), fn ($q, $term) => $q->where(
                fn ($w) => $w->where('reference', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%"),
            ))
            ->with(['listing:id,slug,title,owner_id', 'owner:id,name,email', 'buyer:id,name,email'])
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('admin.offers.index', [
            'offers' => $offers,
            'statuses' => OfferStatus::cases(),
            'filters' => [
                'status' => $request->query('status', ''),
                'q' => $request->query('q', ''),
            ],
        ]);
    }
}
