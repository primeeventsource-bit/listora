<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:190'],
            'phone'   => ['nullable', 'string', 'max:40'],
            'arrive'  => ['nullable', 'date'],
            'depart'  => ['nullable', 'date', 'after_or_equal:arrive'],
            'guests'  => ['nullable', 'integer', 'min:1', 'max:30'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        $listing->inquiries()->create($data);

        return back()->with('sent', "Your message is on its way to {$listing->owner_name}. Replies come straight to your inbox — Listora never sits in the middle of the conversation.");
    }
}
