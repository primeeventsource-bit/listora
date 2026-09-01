@extends('layouts.app')

@section('title', 'How Listora works — for advertisers and visitors')

@section('content')

<div class="page-head photo">
    <img class="bg" src="https://images.unsplash.com/photo-1506477331477-33d5d8b3dc85?auto=format&fit=crop&w=2000&h=900&q=75" alt="" loading="eager">
    <div class="wrap">
        <span class="eyebrow">How it works</span>
        <h1>Publish it. Own the conversation.</h1>
        <p>Listora is an advertising marketplace. Owners pay once to be seen; travelers and buyers deal with them directly. Nobody stands in the middle taking a percentage.</p>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="switch reveal" id="howSwitch">
            <button class="on" data-side="owner">For owners</button>
            <button data-side="traveler">For visitors</button>
        </div>

        <div class="steps" id="stepsOwner">
            <div class="step reveal"><span class="n">01</span><div>
                <h3>Send us your property details</h3>
                <p>One form, about ten minutes. Tell us where the property is, how it sleeps, what is included, and when it is available. The fields exist because an interested visitor asks these questions anyway, and answering them up front saves you the first three messages.</p>
            </div></div>
            <div class="step reveal" id="verification"><span class="n">02</span><div>
                <h3>We verify your ownership</h3>
                <p>Upload your deed, title, or other proof that the property is yours to advertise. Our team checks it against what you entered and comes back to you directly if anything does not line up.</p>
            </div></div>
            <div class="step reveal"><span class="n">03</span><div>
                <h3>Your listing runs for a full year</h3>
                <p>One flat fee covers twelve months. Edit the copy, swap the photos, change the price, or pause it while the property is unavailable — as often as you like, at no extra cost. If it hasn't moved by the end of the year, renew at half price.</p>
            </div></div>
            <div class="step reveal"><span class="n">04</span><div>
                <h3>Inquiries come straight to you</h3>
                <p>Messages land in your Listora inbox and your email. Your address stays private until you reply. We don't sell your inquiry as a lead, we don't pass your details to partners, and we never call you about your own listing.</p>
            </div></div>
            <div class="step reveal"><span class="n">05</span><div>
                <h3>You agree the terms, and you keep everything</h3>
                <p>Price, dates, deposit, paperwork — all between you and the other party. Listora takes no percentage at any point. For ownership transfers we recommend a licensed escrow or closing company, and we will never ask you to route funds through us.</p>
            </div></div>
        </div>

        <div class="steps" id="stepsTraveler" hidden>
            <div class="step reveal"><span class="n">01</span><div>
                <h3>Search by what you want</h3>
                <p>Filter by destination, region, size, and whether it is available to rent or to own. Every listing shows the same facts in the same places, so you are comparing like for like rather than reading around whatever each owner chose to mention.</p>
            </div></div>
            <div class="step reveal"><span class="n">02</span><div>
                <h3>Read a listing written by a person</h3>
                <p>Owners write their own descriptions and we don't rewrite them. You'll find out which corner of the building catches the breeze, which weeks the whales come through, and which month the owner would quietly avoid.</p>
            </div></div>
            <div class="step reveal"><span class="n">03</span><div>
                <h3>Message the owner, not a call center</h3>
                <p>Your message reaches exactly one person. It isn't distributed to a network of agents, and nobody will phone you afterwards. Most owners here reply the same day, and their typical response time is shown on every listing.</p>
            </div></div>
            <div class="step reveal"><span class="n">04</span><div>
                <h3>Confirm what you're getting</h3>
                <p>Ask the owner to confirm the dates in writing before money moves — good owners expect the question. Every listing on Listora has already had its ownership documents checked by our team, but you should still see the confirmation before money moves.</p>
            </div></div>
            <div class="step reveal"><span class="n">05</span><div>
                <h3>Pay carefully, directly</h3>
                <p>For rentals, use a payment method with buyer protection and get the terms in writing. For ownership transfers, use a licensed escrow or closing company — never send funds directly to an individual, and never to anyone claiming to be Listora.</p>
            </div></div>
        </div>
    </div>
</section>

<div class="immersive center">
    <img src="https://images.unsplash.com/photo-1533358122925-6eb2658855bb?auto=format&fit=crop&w=2200&h=1200&q=80" alt="" loading="lazy">
    <div class="wrap">
        <span class="eyebrow">How we make money</span>
        <h2>Listing fees. That's the whole business model.</h2>
        <p>We charge owners a flat fee to advertise for twelve months. We take nothing from the transaction, sell no leads, and accept no payment from resorts or developers for placement. If a listing doesn't work, we'd rather renew it cheaply than take another full fee.</p>
        <a href="{{ route('pricing') }}" class="btn btn-glass btn-lg">See the three plans</a>
    </div>
</div>

<section>
    <div class="wrap-sm">
        <div class="sec-head reveal">
            <span class="eyebrow">Questions</span>
            <h2>Before you list</h2>
        </div>
        @include('partials.faq')
    </div>
</section>

@endsection
