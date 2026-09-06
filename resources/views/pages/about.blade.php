@extends('layouts.app')

@section('title', 'About Listora')

@section('content')

<div class="page-head photo">
    <img class="bg" src="https://images.unsplash.com/photo-1504681869696-d977211a5f4c?auto=format&fit=crop&w=2000&h=900&q=75" alt="" loading="eager">
    <div class="wrap">
        <span class="eyebrow">About</span>
        <h1>We built the place we wished existed</h1>
        <p>Listora exists because advertising a vacation property should not mean handing your phone number to five companies and hoping.</p>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="split">
            <div class="split-media reveal">
                <img src="https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?auto=format&fit=crop&w=900&h=1120&q=80" alt="Villa terrace above the water" loading="lazy">
            </div>
            <div class="reveal">
                <span class="eyebrow">Why we started</span>
                <h2>Owners were being treated as leads, not as people with something good to offer</h2>
                <p class="lead" style="margin-bottom:22px">
                    The people who own these properties are usually delighted with them. They know the place
                    inside out, and there are stretches of the year when it sits empty. What they wanted
                    was somewhere credible to say so.
                </p>
                <p class="muted">
                    Instead they found sites that made money from the transaction — and behaved accordingly. Upfront
                    "marketing" fees. Sales calls at dinner time. Inquiries resold to whoever paid most. The listing
                    itself was almost beside the point.
                </p>
                <p class="muted">
                    Listora charges one flat fee to advertise for 180 days and earns nothing else, from anyone.
                    We do not take commission, we do not sell leads, and we accept no payment from property
                    managers or
                    developers for placement. That constraint is the product.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="band" id="promise">
    <div class="wrap">
        <div class="sec-head center reveal">
            <span class="eyebrow">Our promise</span>
            <h2>Six things we will not do</h2>
            <p>Written down so you can hold us to them.</p>
        </div>

        <div class="grid g3" style="max-width:1080px;margin-inline:auto">
            @php
                $promises = [
                    ['We will never take a commission', 'Not on a rental, not on a transfer, not as a success fee afterwards. The listing fee is our entire revenue from you.'],
                    ['We will never sell your inquiry', 'A message to an owner reaches that owner. It is not distributed to agents, brokers, or partners — not once, not ever.'],
                    ['We will never cold-call you', 'If you list with us, the only calls you get are from people who read your listing and want to talk about it.'],
                    ['We will never publish unverified', 'Every listing has had its ownership documents checked against its details. Buyers rely on that and so do honest owners.'],
                    ['We will never take payment for a deal', 'Money moves between you and the other party. Anyone claiming Listora needs your funds is not us — tell us immediately.'],
                    ['We will never sell placement', 'Property managers and developers cannot pay to appear higher. Ranking is driven by the plan an owner chose and how visitors respond.'],
                ];
            @endphp

            @foreach ($promises as [$t, $d])
                <div class="feature reveal">
                    <span class="chip-icon sm"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3.5v5c0 5-3.4 8.8-8 10-4.6-1.2-8-5-8-10v-5L12 3z"/></svg></span>
                    <div><h3>{{ $t }}</h3><p class="muted">{{ $d }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="safety">
    <div class="wrap">
        <div class="split reverse">
            <div class="split-media reveal">
                <img src="https://images.unsplash.com/photo-1529034502960-57f42a966080?auto=format&fit=crop&w=900&h=1120&q=80" alt="Quiet cove at first light" loading="lazy">
            </div>
            <div class="reveal">
                <span class="eyebrow">Buying and renting safely</span>
                <h2>What we check, and what stays your job</h2>
                <p class="muted">
                    We verify that the person advertising genuinely holds what they say they hold: we review the
                    ownership documents and match them against the property, the location, and the
                    availability in the listing. If it doesn't match, it doesn't publish.
                </p>
                <p class="muted">
                    What we can't do is stand behind the deal itself. Before money moves, ask for the club confirmation
                    in your name or the deed reference — reasonable owners expect this. For rentals, use a payment
                    method with buyer protection. For ownership transfers, use a licensed escrow or closing company.
                </p>
                <p class="muted">
                    And a hard rule: Listora will never ask you to send funds to us, and no legitimate transaction here
                    ever requires it. If anyone tells you otherwise, that is a fraud attempt — report it to us and we
                    will remove the account the same day.
                </p>
                <a href="{{ route('how') }}" class="btn btn-outline" style="margin-top:12px">Read the full process</a>
            </div>
        </div>
    </div>
</section>

<section class="band pad-sm" id="terms">
    <div class="wrap">
        <div class="grid g4">
            <div class="reveal"><div style="font-family:var(--font);font-size:44px;color:var(--teal-dark)">{{ number_format($total) }}</div><div class="muted" style="font-size:13px;letter-spacing:.14em;text-transform:uppercase;font-weight:600">Live listings</div></div>
            <div class="reveal"><div style="font-family:var(--font);font-size:44px;color:var(--teal-dark)">{{ number_format($regions) }}</div><div class="muted" style="font-size:13px;letter-spacing:.14em;text-transform:uppercase;font-weight:600">{{ Str::plural('Destination', $regions) }}</div></div>
            <div class="reveal"><div style="font-family:var(--font);font-size:44px;color:var(--teal-dark)">$0</div><div class="muted" style="font-size:13px;letter-spacing:.14em;text-transform:uppercase;font-weight:600">Commission taken</div></div>
            <div class="reveal"><div style="font-family:var(--font);font-size:44px;color:var(--teal-dark)">{{ $verified_pct }}%</div><div class="muted" style="font-size:13px;letter-spacing:.14em;text-transform:uppercase;font-weight:600">Ownership verified</div></div>
        </div>
    </div>
</section>

<div class="immersive center">
    <img src="https://images.unsplash.com/photo-1620898670223-6f7f07d82a3b?auto=format&fit=crop&w=2200&h=1200&q=80" alt="" loading="lazy">
    <div class="wrap">
        <span class="eyebrow">Get in touch</span>
        <h2>We answer our own email</h2>
        <p>Questions about listing, verification, or a message you've received — write to us and a person replies, usually same day.</p>
        <a href="mailto:{{ config('listora.brand.email') }}" class="btn btn-glass btn-lg">{{ config('listora.brand.email') }}</a>
    </div>
</div>

@endsection
