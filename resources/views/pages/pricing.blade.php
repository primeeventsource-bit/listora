@extends('layouts.app')

@section('title', 'Pricing — one flat fee, no commission | Listora')

@section('content')

<div class="page-head plain">
    <div class="wrap">
        <span class="eyebrow">Pricing</span>
        <h1>One fee. 180 days. No cut of your deal.</h1>
        <p>Every plan includes ownership verification, unlimited edits, and direct messaging. What changes between them is how many properties you can advertise and how hard we work to put them in front of people.</p>
    </div>
</div>

<section>
    <div class="wrap">
        @include('partials.tiers', ['plans' => $plans])

        <p class="center muted" style="margin-top:34px;font-size:15px;max-width:60ch;margin-inline:auto">
            Billed upfront and valid for 180 days from the day your advertising publishes.
            Renew at half price on Starter and Explorer, free on Signature.
        </p>
    </div>
</section>

<section class="band">
    <div class="wrap">
        <div class="sec-head center reveal">
            <span class="eyebrow">What every plan includes</span>
            <h2>The parts we don't charge extra for</h2>
        </div>

        <div class="grid g3" style="max-width:1060px;margin-inline:auto">
            @php
                $inc = [
                    ['Ownership verification', 'Our team reviews your deed, title, or other proof that the property is yours to advertise before your listing publishes. Never an add-on.'],
                    ['Unlimited photos', 'Up to twenty images on Starter and forty above it — with no per-photo charge on any plan.'],
                    ['Unlimited edits', 'Change your price, dates, copy, or photos as often as you want for the whole 180 days.'],
                    ['Direct messaging', 'Inquiries arrive in your inbox with your email address kept private until you reply.'],
                    ['Pause any time', 'Property not available for a while? Pause the listing and restart it later without losing days.'],
                    ['No commission, ever', 'Whatever you agree with a visitor or buyer is entirely yours. We take no percentage at any stage.'],
                ];
            @endphp

            @foreach ($inc as [$t, $d])
                <div class="feature reveal">
                    <span class="chip-icon sm">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 12.5l5 5L20 6.5"/></svg>
                    </span>
                    <div><h3>{{ $t }}</h3><p class="muted">{{ $d }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<div class="immersive center">
    <img src="https://images.unsplash.com/photo-1545556124-500dc7c01f2c?auto=format&fit=crop&w=2200&h=1200&q=80" alt="" loading="lazy">
    <div class="wrap">
        <span class="eyebrow">Ready when you are</span>
        <h2>Ten minutes now, 180 days of visibility</h2>
        <p>Start your listing and pick a plan at the end — nothing is charged until you have seen exactly how it will look.</p>
        <a href="{{ route('list.create') }}" class="btn btn-amber btn-lg">List Your Property</a>
    </div>
</div>

@endsection
