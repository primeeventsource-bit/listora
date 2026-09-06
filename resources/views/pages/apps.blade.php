@extends('layouts.app')

@section('title', 'Listora for iOS and Android')

@section('content')

<div class="page-head plain">
    <div class="wrap">
        <span class="eyebrow">iOS &amp; Android</span>
        <h1>The whole platform, in your pocket</h1>
        <p>Search on the train. Answer an inquiry from the beach. Get a push the moment someone asks about your week.</p>
        <div style="margin-top:24px">
            <a class="store" href="#"><svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.4 12.8c0-2.3 1.9-3.4 2-3.5-1.1-1.6-2.8-1.8-3.4-1.8-1.4-.1-2.8.9-3.5.9-.7 0-1.8-.8-3-.8-1.5 0-3 .9-3.8 2.3-1.6 2.8-.4 7 1.2 9.3.8 1.1 1.7 2.4 2.9 2.3 1.2 0 1.6-.7 3-.7s1.8.7 3 .7c1.2 0 2-1.1 2.8-2.3.9-1.3 1.2-2.6 1.2-2.6s-2.4-.9-2.4-3.8zM14.2 5.5c.6-.8 1-1.9.9-3-.9 0-2 .6-2.7 1.4-.6.7-1.1 1.8-.9 2.9 1 .1 2.1-.5 2.7-1.3z"/></svg><span><span class="s1">Download on the</span><span class="s2">App Store</span></span></a>
            <a class="store" href="#"><svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.6 2.3c-.3.3-.5.8-.5 1.4v16.6c0 .6.2 1.1.5 1.4l.1.1 9.3-9.3v-.2L3.6 2.3zM16.3 15.6l-3.1-3.1v-.2l3.1-3.1.1.1 3.7 2.1c1.1.6 1.1 1.6 0 2.2l-3.8 2zM15.9 16.1l-3.2-3.2-9.1 9.1c.4.4 1 .4 1.7 0l10.6-5.9M15.9 8.4L5.3 2.5c-.7-.4-1.3-.4-1.7 0l9.1 9.1 3.2-3.2z"/></svg><span><span class="s1">Get it on</span><span class="s2">Google Play</span></span></a>
        </div>
    </div>
</div>

<section>
    <div class="wrap">
        <div class="sec-head center reveal">
            <span class="eyebrow">Screens</span>
            <h2>Designed for the two things people actually do</h2>
            <p>Find somewhere worth going, and answer the person asking about it.</p>
        </div>

        <div class="phone-stack reveal" style="gap:32px">
            {{-- discover --}}
            @php $a = $sample->get(0); $b = $sample->get(1); $c = $sample->get(2); @endphp

            <div>
                <div class="phone small">
                    <div class="notch"></div>
                    <div class="screen">
                        @if ($a)
                            <div class="ph-hero" style="height:46%">
                                <img src="{{ $a->photoUrl(0, 600, 700) }}" alt="" loading="lazy">
                                <div class="cap"><div class="s">{{ $a->location }}</div><div class="t">{{ Str::limit($a->title, 40) }}</div></div>
                            </div>
                        @endif
                        <div class="ph-search"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg>Search destinations, cities, properties</div>
                        <div class="ph-body" style="padding-top:0">
                            @foreach ($sample->skip(1)->take(3) as $r)
                                <div class="ph-row">
                                    <span class="ph-thumb"><img src="{{ $r->photoUrl(1, 200, 170) }}" alt="" loading="lazy"></span>
                                    <span><span class="t">{{ Str::limit($r->title, 24) }}</span><span class="s">{{ $r->kind_label }}</span></span>
                                    <span class="p">{{ $r->price_formatted }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="ph-tabbar">
                            <span class="act"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10.5 12 3l9 7.5V21H3z"/></svg></span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg></span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 21.5l8.8-8.8a5 5 0 0 0 0-7.1z"/></svg></span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.4A8.4 8.4 0 1 1 21 11.5z"/></svg></span>
                        </div>
                    </div>
                </div>
                <p class="center muted" style="margin-top:16px;font-size:13.5px;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Discover</p>
            </div>

            {{-- listing detail --}}
            <div>
                <div class="phone small">
                    <div class="notch"></div>
                    <div class="screen">
                        @if ($b)
                            <div class="ph-hero" style="height:38%">
                                <img src="{{ $b->photoUrl(0, 600, 600) }}" alt="" loading="lazy">
                            </div>
                            <div class="ph-body">
                                <div style="font-size:8.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--slate);font-weight:700">{{ $b->location }}</div>
                                <div style="font-family:var(--font);font-size:15px;line-height:1.3;margin:5px 0 8px">{{ Str::limit($b->title, 44) }}</div>
                                <div style="display:flex;gap:5px;flex-wrap:wrap;margin-bottom:10px">
                                    <span style="font-size:7.5px;letter-spacing:.1em;text-transform:uppercase;font-weight:700;background:var(--teal-tint);color:var(--teal-dark);padding:4px 8px;border-radius:100px">{{ $b->kind_label }}</span>
                                    <span style="font-size:7.5px;letter-spacing:.1em;text-transform:uppercase;font-weight:700;background:var(--teal-tint);color:var(--teal);padding:4px 8px;border-radius:100px">Verified owner</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;padding:9px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line);font-size:9px;color:var(--slate)">
                                    <span><b style="display:block;font-family:var(--font);font-size:13px;color:var(--ink)">{{ $b->bedrooms }}</b>Bedrooms</span>
                                    <span><b style="display:block;font-family:var(--font);font-size:13px;color:var(--ink)">{{ $b->sleeps }}</b>Sleeps</span>
                                    <span><b style="display:block;font-family:var(--font);font-size:13px;color:var(--ink)">{{ $b->season ?: 'Annual' }}</b>Season</span>
                                </div>
                                <div style="font-size:9.5px;color:var(--slate);line-height:1.6;margin-top:10px">{{ Str::limit($b->headline, 96) }}</div>
                            </div>
                            <div style="position:absolute;left:0;right:0;bottom:0;padding:12px 14px 20px;background:rgba(255,255,255,.97);border-top:1px solid var(--line);display:flex;align-items:center;gap:10px">
                                <span style="font-family:var(--font);font-size:17px">{{ $b->price_formatted }}<span style="font-family:var(--font);font-size:9px;color:var(--slate);margin-left:3px">{{ $b->price_unit_label }}</span></span>
                                <span style="margin-left:auto;background:var(--navy);color:#fff;font-size:9.5px;font-weight:600;padding:9px 16px;border-radius:100px">Message owner</span>
                            </div>
                        @endif
                    </div>
                </div>
                <p class="center muted" style="margin-top:16px;font-size:13.5px;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Listing</p>
            </div>

            {{-- owner inbox --}}
            <div>
                <div class="phone small">
                    <div class="notch"></div>
                    <div class="screen" style="background:#fff">
                        <div style="padding:46px 16px 12px;background:var(--navy);color:#fff">
                            <div style="font-size:8.5px;letter-spacing:.2em;text-transform:uppercase;color:var(--teal);font-weight:700">Owner inbox</div>
                            <div style="font-family:var(--font);font-size:19px;margin-top:5px">3 new inquiries</div>
                        </div>
                        <div class="ph-body">
                            @foreach ([['Raymond T.', 'Is week 26 still open for 2027?', '2m'], ['Nadia H.', 'Could we split the points across two stays?', '1h'], ['Paul & Erin', 'We can be flexible on arrival day —', '4h'], ['Clara V.', 'Do you allow a small dog?', 'Yesterday']] as [$who, $msg, $when])
                                <div class="ph-row" style="align-items:flex-start">
                                    <span class="avatar" style="width:30px;height:30px;font-size:11px">{{ Str::substr($who, 0, 1) }}</span>
                                    <span>
                                        <span class="t">{{ $who }}</span>
                                        <span class="s">{{ Str::limit($msg, 30) }}</span>
                                    </span>
                                    <span class="p" style="font-family:var(--font);font-size:8.5px;color:var(--slate)">{{ $when }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="ph-tabbar">
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10.5 12 3l9 7.5V21H3z"/></svg></span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg></span>
                            <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 21.5l8.8-8.8a5 5 0 0 0 0-7.1z"/></svg></span>
                            <span class="act"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.4A8.4 8.4 0 1 1 21 11.5z"/></svg></span>
                        </div>
                    </div>
                </div>
                <p class="center muted" style="margin-top:16px;font-size:13.5px;letter-spacing:.12em;text-transform:uppercase;font-weight:600">Owner inbox</p>
            </div>
        </div>
    </div>
</section>

<section class="band">
    <div class="wrap">
        <div class="grid g3" style="max-width:1060px;margin-inline:auto">
            @php
                $f = [
                    ['Push the moment it matters', 'A new inquiry about your listing reaches your phone in seconds. Reply from the notification without opening the app.'],
                    ['Saved searches that watch for you', 'Tell the app what you want — a region, a week number, a points range — and it tells you the day something matching publishes.'],
                    ['Everything works offline', 'Saved listings, your inbox history, and your own listing details stay readable with no signal. Useful, given where people use this.'],
                    ['Photos straight from your camera roll', 'Add or reorder listing photos from your phone. Changes go live immediately, no desktop needed.'],
                    ['Face ID on your inbox', 'Your inquiries and contact details sit behind biometric lock by default.'],
                    ['One account, both sides', 'Advertise a week and book someone else\'s from the same profile. Most of our members do both.'],
                ];
            @endphp
            @foreach ($f as [$t, $d])
                <div class="feature reveal">
                    <span class="chip-icon sm"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2.5" width="12" height="19" rx="2.6"/><path d="M11 18.6h2"/></svg></span>
                    <div><h3>{{ $t }}</h3><p class="muted">{{ $d }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@endsection
