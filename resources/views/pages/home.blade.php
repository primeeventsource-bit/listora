@extends('layouts.app', ['overPhoto' => true])

@section('title', $seo->title())
@section('meta', $seo->description())
@section('robots', $seo->robots())

@section('head')
    <link rel="canonical" href="{{ $seo->canonical() }}">

    {{-- Organization + WebSite. The SearchAction points at Explore, which is
         where a query actually resolves. See App\Services\Seo\HomeSeo. --}}
    <script type="application/ld+json">{!! $seo->jsonLd() !!}</script>
@endsection

@section('content')

@php
    // Points and vacation-week categories are withheld from the public site
    // while payment underwriting is in progress. Same flag as
    // Listing::scopePublished, and the same fail-closed default, so the copy
    // and the catalogue can never disagree about what is on offer.
    $showTimeshare = feature('timeshare_categories', null, false);

    $offeredKinds = \App\Models\Listing::offeredKinds();
@endphp

{{-- ============================== HERO ============================== --}}
<section class="hero on-photo">
    <div class="hero-media">
        <img src="https://images.unsplash.com/photo-1613843841925-6af6ed0df472?auto=format&fit=crop&w=2400&h=1400&q=80"
             onerror="this.src='https://images.unsplash.com/photo-1505118380757-91f5f5632de0?auto=format&fit=crop&w=2400&h=1400&q=80'"
             alt="Infinity pool overlooking the ocean at sunset" fetchpriority="high" width="2400" height="1400">
    </div>

    <div class="wrap">
        <span class="eyebrow">Vacation Property Advertising</span>
        <h1>Advertise Your Vacation Property.<br><span class="accent">Reach More Interested Visitors.</span></h1>
        <p class="lead">
            Listora is a digital advertising and listing platform that helps vacation property owners
            showcase their properties, increase visibility, and connect directly with interested visitors.
        </p>

        <div class="hero-cta">
            <a href="{{ route('list.create') }}" class="btn btn-primary btn-lg">Advertise Your Property</a>
            <a href="{{ route('listings.index') }}" class="btn btn-glass btn-lg">Explore Listings</a>
        </div>

        <form class="searchbar" action="{{ route('listings.index') }}" method="GET" role="search">
            <div class="fld">
                <label for="q">Keyword</label>
                <input type="text" id="q" name="q" placeholder="Destination, property, or listing" autocomplete="off">
            </div>
            <div class="fld">
                <label for="kind">What</label>
                <select id="kind" name="kind">
                    <option value="all">Anything</option>
                    @foreach ($offeredKinds as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fld">
                <label for="mode">Rent or own</label>
                <select id="mode" name="mode">
                    <option value="all">Either</option>
                    <option value="rent">Rent</option>
                    <option value="own">Own</option>
                </select>
            </div>
            <div class="fld">
                <label for="region">Region</label>
                <select id="region" name="region">
                    <option value="all">Anywhere</option>
                    @foreach (config('listora.regions') as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-navy">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/>
                </svg>
                Search
            </button>
        </form>

        <div class="hero-stats">
            {{-- Every figure here is counted from the catalogue. Nothing in
                 this row is a claim the data cannot support: see the note in
                 HomeController on what these used to say. --}}
            <div><span class="n tnum">{{ number_format($counts['total']) }}</span><span class="l">Live listings</span></div>
            <div><span class="n tnum">{{ number_format($counts['regions']) }}</span><span class="l">{{ Str::plural('Destination', $counts['regions']) }}</span></div>
            <div><span class="n">$0</span><span class="l">Commission taken</span></div>
            <div><span class="n tnum">{{ $counts['verified_pct'] }}%</span><span class="l">Ownership verified</span></div>
        </div>
    </div>
</section>

{{-- ============================= BENEFITS ============================ --}}
<div class="benefits">
    <div class="wrap">
        <div class="row">
            <div class="benefit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 11l18-7-7 18-2.5-8.5L3 11z"/></svg>
                <div>
                    <h4>Advertise Easily</h4>
                    <p>Manage your advertisement, availability and inquiries in one place.</p>
                </div>
            </div>
            <div class="benefit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                <div>
                    <h4>Get More Visibility</h4>
                    <p>Reach more people through our platform and marketing.</p>
                </div>
            </div>
            <div class="benefit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 20v-1.6a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V20"/><circle cx="9" cy="7.5" r="3.5"/><path d="M22 20v-1.6a4 4 0 0 0-3-3.9M16.5 4.2a4 4 0 0 1 0 7.1"/></svg>
                <div>
                    <h4>Connect Directly</h4>
                    <p>Communicate safely with interested users.</p>
                </div>
            </div>
            <div class="benefit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="6" y="2.5" width="12" height="19" rx="2.6"/><path d="M11 18.6h2"/></svg>
                <div>
                    <h4>Manage Anywhere</h4>
                    <p>Access your account from the web or mobile app.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================ CATEGORIES ============================ --}}
<section>
    <div class="wrap">
        <div class="sec-head center reveal">
            <span class="eyebrow">How Listora works</span>
            <h2>Advertise your property</h2>
            <p>
                We create your property advertisement, showcase your availability, you receive
                inquiries and offers, and communicate directly — through one easy-to-use platform.
            </p>
        </div>

        <div class="grid {{ $showTimeshare ? 'g3' : 'g1' }}">
            @php
                $cats = array_values(array_filter([
                    ['home',   'Vacation Properties', 'Advertise your property and get connected with interested visitors.',
                        '<path d="M3 10.5 12 3l9 7.5V21H3z"/><path d="M9.5 21v-6h5v6"/>'],
                    $showTimeshare ? ['points', 'Resort Club Points',  'List your available points and connect with potential buyers.',
                        '<path d="M12 3l2.7 5.8 6.3.8-4.6 4.3 1.2 6.3L12 17.2 6.4 20.2l1.2-6.3L3 9.6l6.3-.8L12 3z"/>'] : null,
                    $showTimeshare ? ['weeks',  'Vacation Weeks',      'Advertise your available weeks and reach more interested users.',
                        '<rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>'] : null,
                ]));
            @endphp

            @foreach ($cats as [$key, $label, $blurb, $icon])
                @php $cover = $covers[$key] ?? null; @endphp
                <a href="{{ route('listings.index', ['kind' => $key]) }}" class="cat reveal">
                    @if ($cover)
                        <img src="{{ $cover->photoUrl(0, 900, 1100) }}" alt="{{ $label }}" loading="lazy" width="900" height="1100">
                    @endif
                    <span class="cat-count tnum">{{ $counts[$key] }} listings</span>
                    <div class="cat-body">
                        <span class="chip-icon sm">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $icon !!}</svg>
                        </span>
                        <h3>{{ $label }}</h3>
                        <p>{{ $blurb }}</p>
                        <span class="cat-more">
                            Explore
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================= FEATURED ============================= --}}
<section class="band">
    <div class="wrap">
        <div class="sec-head-row reveal">
            <div class="sec-head" style="max-width:620px">
                <span class="eyebrow">Featured listings</span>
                <h2>Hand-picked, owner published</h2>
                <p>Every listing here was written by its owner and verified by our team before it went live.</p>
            </div>
            <a href="{{ route('listings.index') }}" class="btn btn-outline">View all listings</a>
        </div>

        <div class="grid g3">
            @foreach ($featured as $listing)
                <x-listing-card :listing="$listing"/>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================ HOW IT WORKS ========================== --}}
<section>
    <div class="wrap">
        <div class="sec-head center reveal">
            <span class="eyebrow">How it works</span>
            <h2>Simple on both sides</h2>
        </div>

        {{--
            The two sides carry different accents (owner teal, visitor amber)
            and each button hands its accent to the four steps it controls, so
            clicking a tab visibly changes the 1-2-3-4 underneath rather than
            swapping four identical-looking cards. `aria-pressed` and
            `aria-controls` say the same thing to a screen reader that the
            colour says to everyone else.
        --}}
        <div class="center" style="margin-bottom:0">
            <div class="switch reveal" id="howSwitch" role="group" aria-label="Whose steps to show">
                <button type="button" class="on" data-side="owner"
                        aria-pressed="true" aria-controls="stepsOwner">I'm advertising</button>
                <button type="button" data-side="visitor"
                        aria-pressed="false" aria-controls="stepsVisitor">I'm looking</button>
            </div>
        </div>

        <div class="grid g4 steps steps-owner is-active" id="stepsOwner">
            @foreach ([
                ['Send us your property details', 'Tell us about the property — photos, location, description, amenities, features, and when it is available. Our team verifies ownership and builds the advertisement for you.'],
                ['Get discovered', 'Visitors find your advertisement through Listora and through digital advertising campaigns designed to increase exposure. Every member receives a unique advertising number and a dedicated advertising URL.'],
                ['Your listing runs 180 days', 'One flat fee covers 180 days. Edit it whenever you like, pause it when the property is no longer available, and renew at half price if it has not moved.'],
                ['Communicate directly', 'Advertisers and interested visitors communicate through the Listora platform. Listora provides the advertising and communication technology; the parties control their own discussions and arrangements.'],
            ] as $i => [$t, $d])
                <div class="step-card reveal">
                    <span class="num">{{ $i + 1 }}</span>
                    <h3>{{ $t }}</h3>
                    <p>{{ $d }}</p>

                    {{-- Step 2 is the one with something to do, so it gets the link. --}}
                    @if ($i === 1)
                        <a href="{{ route('property-information.create') }}"
                           class="btn btn-outline btn-sm" style="margin-top:16px">
                            Property information sheet
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="grid g4 steps steps-visitor" id="stepsVisitor" hidden>
            @foreach ([
                ['Find a property', 'Browse vacation properties advertised by owners across popular destinations. Filter by destination, region, size, and availability.'],
                ['Read a real listing', 'Owners write their own descriptions. You\'ll learn which corner catches the breeze and which month to avoid.'],
                ['Submit an inquiry or make an offer', 'Your message reaches one person: the advertiser. It is never resold as a lead and nobody calls you afterwards.'],
                ['Agree terms directly', 'You and the owner settle dates, price, and paperwork between yourselves — we publish guidance on doing it safely.'],
            ] as $i => [$t, $d])
                <div class="step-card reveal">
                    <span class="num">{{ $i + 1 }}</span>
                    <h3>{{ $t }}</h3>
                    <p>{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================= IMMERSIVE ============================ --}}
<div class="immersive">
    <img src="https://images.unsplash.com/photo-1541417904950-b855846fe074?auto=format&fit=crop&w=2200&h=1200&q=80"
         alt="Palm-lined shoreline at golden hour" loading="lazy" width="2200" height="1200">
    <div class="wrap">
        <span class="eyebrow">The difference</span>
        <h2>Most platforms earn when you make a deal. We don't.</h2>
        <p>That single fact changes everything about how we behave — what we publish, who we call, and what happens the day your listing works.</p>
        <a href="{{ route('how') }}" class="btn btn-glass btn-lg">See how we make money</a>
    </div>
</div>

{{-- ============================== WHY US ============================== --}}
<section>
    <div class="wrap">
        <div class="sec-head center reveal">
            <span class="eyebrow">Why Listora</span>
            <h2>Built to stay out of the way</h2>
        </div>

        <div class="grid g2" style="gap:40px 52px;max-width:1020px;margin-inline:auto">
            @php
                $why = [
                    ['One flat fee. No commission.', 'You pay to advertise, not to transact. When you rent or sell your property, the full amount is yours. We never take a percentage and never ask for one later.',
                        '<path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>'],
                    ['Direct contact, always', 'Messages go straight between the two of you. No sales team on the line, no lead resold to three other companies, no callback you never asked for.',
                        '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.4A8.4 8.4 0 1 1 21 11.5z"/>'],
                    ['Advertising performance you can see', 'Every advertiser gets views, unique visitors, inquiries, offers, traffic sources, and an approximate geographic traffic map for their own advertisement.',
                        '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'],
                ];
            @endphp

            @foreach ($why as [$t, $d, $icon])
                <div class="feature reveal">
                    <span class="chip-icon sm">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $icon !!}</svg>
                    </span>
                    <div><h3>{{ $t }}</h3><p>{{ $d }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================= NEWEST ============================== --}}
<section class="band pad-sm">
    <div class="wrap">
        <div class="sec-head-row reveal">
            <div class="sec-head" style="max-width:560px">
                <span class="eyebrow">Just listed</span>
                <h2>New this month</h2>
            </div>
            <a href="{{ route('listings.index', ['sort' => 'newest']) }}" class="btn btn-outline">See what's new</a>
        </div>
        <div class="grid g4">
            @foreach ($recent as $listing)
                <x-listing-card :listing="$listing"/>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== QUOTES ============================= --}}
<section>
    <div class="wrap">
        <div class="grid g3">
            @foreach ([
                ['I\'d advertised my place in three other spots and got nothing but calls from companies wanting money up front. Here I paid once, a real family messaged me in nine days, and that was that.', 'DM', 'Denise M.', 'Advertiser · Hilton Head, SC'],
                ['The listings actually tell you what you are getting. I messaged the owner in Maui, we agreed dates in a day, and there was nobody in the middle taking a cut.', 'RT', 'Raymond T.', 'Visitor · Denver, CO'],
                ['Verification took two days and they caught that my listing had the wrong availability on it. Fixing that before publishing probably saved me a very awkward conversation.', 'AP', 'Angela P.', 'Advertiser · Orlando, FL'],
            ] as [$text, $ini, $name, $role])
                <div class="quote reveal">
                    <div class="stars" aria-label="5 out of 5">★★★★★</div>
                    <p>“{{ $text }}”</p>
                    <div class="who"><span class="avatar">{{ $ini }}</span><span><b style="color:var(--navy)">{{ $name }}</b><br>{{ $role }}</span></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================== APPS =============================== --}}
<section class="band-teal">
    <div class="wrap">
        <div class="split">
            <div class="split-media reveal" style="aspect-ratio:auto;box-shadow:none;background:transparent;border-radius:0;display:grid;place-items:center">
                @include('partials.phone', ['listings' => $featured])
            </div>
            <div class="reveal">
                <span class="eyebrow">iOS &amp; Android</span>
                <h2>Manage anywhere</h2>
                <p class="lead">Answer inquiries from the beach. Get a push the moment someone messages about your week. Search every live listing on the train home and save the ones worth a second look.</p>
                <div style="margin-top:10px">
                    <a class="store light" href="{{ route('apps') }}">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.4 12.8c0-2.3 1.9-3.4 2-3.5-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.1-2.8.9-3.5.9-.7 0-1.8-.8-3-.8-1.5 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.4 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7c1.2 0 2-1.1 2.8-2.3.9-1.3 1.2-2.6 1.2-2.6s-2.4-.9-2.4-3.8zM14.2 5.5c.6-.8 1-1.9.9-3-.9 0-2 .6-2.7 1.4-.6.7-1.1 1.8-.9 2.9 1 .1 2.1-.5 2.7-1.3z"/></svg>
                        <span><span class="s1">Download on the</span><span class="s2">App Store</span></span>
                    </a>
                    <a class="store light" href="{{ route('apps') }}">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.6 2.3c-.3.3-.5.8-.5 1.4v16.6c0 .6.2 1.1.5 1.4l.1.1 9.3-9.3v-.2L3.6 2.3zM16.3 15.6l-3.1-3.1v-.2l3.1-3.1.1.1 3.7 2.1c1.1.6 1.1 1.6 0 2.2l-3.8 2zM15.9 16.1l-3.2-3.2-9.1 9.1c.4.4 1 .4 1.7 0l10.6-5.9M15.9 8.4L5.3 2.5c-.7-.4-1.3-.4-1.7 0l9.1 9.1 3.2-3.2z"/></svg>
                        <span><span class="s1">Get it on</span><span class="s2">Google Play</span></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================== PRICING ============================ --}}
<section>
    <div class="wrap">
        <div class="sec-head center reveal">
            <span class="eyebrow">Pricing</span>
            <h2>One fee. 180 days. No cut of your deal.</h2>
            <p>Every plan includes ownership verification, unlimited edits, and direct messaging.</p>
        </div>

        @include('partials.tiers', ['plans' => config('listora.plans')])

        <p class="center muted" style="margin-top:28px;font-size:14.5px">
            Billed once per listing, covering 180 days. Renew at half price if it hasn't moved.
        </p>
    </div>
</section>

{{-- ================================ FAQ ============================== --}}
<section class="band">
    <div class="wrap-sm">
        <div class="sec-head reveal">
            <span class="eyebrow">Questions</span>
            <h2>The things people ask first</h2>
        </div>
        @include('partials.faq')
    </div>
</section>

{{-- ================================ CTA ============================== --}}
<div class="immersive">
    <img src="https://images.unsplash.com/photo-1504681869696-d977211a5f4c?auto=format&fit=crop&w=2200&h=1200&q=80"
         alt="Quiet cove at dusk" loading="lazy" width="2200" height="1200">
    <div class="wrap">
        <span class="eyebrow">Ready when you are</span>
        <h2>Put your listing in front of people already looking</h2>
        <p>Create it in about ten minutes. We verify within two business days, and it runs for 180 days.</p>
        <a href="{{ route('list.create') }}" class="btn btn-amber btn-lg">Get Started</a>
    </div>
</div>

@endsection
