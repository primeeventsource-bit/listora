@extends('layouts.app')

@section('title', $listing->title.' — Listora')
@section('meta', Str::limit(strip_tags($listing->headline ?: $listing->description), 155))

@section('content')

<section style="padding-top:calc(var(--nav-h) + 40px);padding-bottom:0">
    <div class="wrap">
        <a href="{{ route('listings.index', ['kind' => $listing->kind]) }}"
           style="display:inline-flex;align-items:center;gap:9px;font-size:13.5px;font-weight:600;color:var(--slate);margin-bottom:22px">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12H5M11 6l-6 6 6 6"/></svg>
            All {{ \App\Models\Listing::KINDS[$listing->kind] }}
        </a>

        <div class="gallery">
            <div class="main">
                <img src="{{ $listing->photoUrl(0, 1400, 1100) }}" alt="{{ $listing->title }}" width="1400" height="1100">
            </div>
            <div class="side"><img src="{{ $listing->photoUrl(1, 700, 540) }}" alt="" loading="lazy"></div>
            <div class="side"><img src="{{ $listing->photoUrl(2, 700, 540) }}" alt="" loading="lazy"></div>
        </div>
    </div>
</section>

<section style="padding-top:0">
    <div class="wrap">
        <div class="detail">

            {{-- --------------------------------- main --------------------------------- --}}
            <div>
                <div class="detail-loc">
                    {{ $listing->location }}
                    @if ($listing->resort_name) &middot; {{ $listing->resort_name }} @endif
                </div>

                <h1>{{ $listing->title }}</h1>

                @if ($listing->headline)
                    <p style="font-family:var(--font);font-size:21px;line-height:1.5;color:var(--slate);font-style:italic;max-width:56ch">
                        {{ $listing->headline }}
                    </p>
                @endif

                <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:20px">
                    <span class="tag" style="position:static;background:var(--navy)">{{ \App\Models\Listing::KINDS[$listing->kind] }}</span>
                    <span class="tag" style="position:static;background:var(--teal-tint);color:var(--teal-dark)">{{ \App\Models\Listing::MODES[$listing->mode] }}</span>
                    @if ($listing->is_verified)
                        <span class="tag verified" style="position:static">Ownership verified</span>
                    @endif
                    @if ($listing->club_name)
                        <span class="tag" style="position:static;background:var(--teal-tint);color:var(--teal-dark)">{{ $listing->club_name }}</span>
                    @endif
                </div>

                {{-- specs --}}
                <div class="specs">
                    @if ($listing->kind === 'points')
                        <div class="spec"><div class="k">Club points</div><div class="v tnum">{{ number_format($listing->points) }}</div></div>
                        <div class="spec"><div class="k">Season</div><div class="v">{{ $listing->season ?: '—' }}</div></div>
                        <div class="spec"><div class="k">Usage</div><div class="v">{{ $listing->usage ?: 'Annual' }}</div></div>
                        <div class="spec"><div class="k">Books up to</div><div class="v">{{ $listing->bedrooms }} bed</div></div>
                    @elseif ($listing->kind === 'weeks')
                        <div class="spec"><div class="k">Week</div><div class="v tnum">{{ $listing->week_number }}</div></div>
                        <div class="spec"><div class="k">Season</div><div class="v">{{ $listing->season ?: '—' }}</div></div>
                        <div class="spec"><div class="k">Unit</div><div class="v">{{ $listing->bedrooms }} bed &middot; sleeps {{ $listing->sleeps }}</div></div>
                        <div class="spec"><div class="k">Usage</div><div class="v">{{ $listing->usage ?: 'Annual' }}</div></div>
                    @else
                        <div class="spec"><div class="k">Bedrooms</div><div class="v tnum">{{ $listing->bedrooms }}</div></div>
                        <div class="spec"><div class="k">Bathrooms</div><div class="v tnum">{{ rtrim(rtrim(number_format($listing->bathrooms, 1), '0'), '.') }}</div></div>
                        <div class="spec"><div class="k">Sleeps</div><div class="v tnum">{{ $listing->sleeps }}</div></div>
                        <div class="spec"><div class="k">Unit type</div><div class="v" style="font-size:17px">{{ $listing->unit_type }}</div></div>
                    @endif
                </div>

                @if ($listing->available_from || $listing->available_to)
                    <div class="notice amber">
                        <strong>Availability:</strong>
                        @if ($listing->available_from && $listing->available_to)
                            {{ $listing->available_from->format('j F Y') }} — {{ $listing->available_to->format('j F Y') }}
                        @elseif ($listing->available_to)
                            must be used by {{ $listing->available_to->format('j F Y') }}
                        @else
                            from {{ $listing->available_from->format('j F Y') }}
                        @endif
                    </div>
                @endif

                <h2 style="font-size:27px;margin-bottom:18px">From the owner</h2>
                <div class="prose">
                    @foreach (preg_split("/\n+/", $listing->description) as $para)
                        <p>{{ $para }}</p>
                    @endforeach
                </div>

                @if ($listing->amenities)
                    <h2 style="font-size:27px;margin:44px 0 6px">What's included</h2>
                    <div class="amenities">
                        @foreach ($listing->amenities as $a)
                            <div><span class="dot"></span>{{ $a }}</div>
                        @endforeach
                    </div>
                @endif

                @if ($listing->maintenance_fee)
                    <h2 style="font-size:27px;margin:44px 0 12px">Ongoing costs</h2>
                    <p class="muted" style="max-width:58ch">
                        Annual dues are currently <b style="color:var(--ink)">${{ number_format($listing->maintenance_fee) }}</b>,
                        billed by the club or association rather than by Listora. The owner has confirmed dues are current.
                        Ask them directly for the last three years of statements — most are happy to share them.
                    </p>
                @endif

                <div style="margin-top:44px;padding:26px 28px;border:1px solid var(--line);border-radius:18px;background:#fff">
                    <h3 style="font-size:19px;margin-bottom:10px">Listing reference {{ $listing->reference }}</h3>
                    <p class="muted" style="margin:0;font-size:14.5px">
                        Published {{ $listing->published_at?->diffForHumans() }} &middot;
                        {{ number_format($listing->views) }} views &middot;
                        saved by {{ $listing->saves }} people.
                        Quote this reference when you message the owner.
                    </p>
                </div>
            </div>

            {{-- -------------------------------- aside -------------------------------- --}}
            <aside class="owner-card">
                @if (session('sent'))
                    <div class="notice">{{ session('sent') }}</div>
                @endif

                <div class="owner-price">
                    <div class="big">{{ $listing->price_formatted }}<span style="font-family:var(--font);font-size:15px;color:var(--slate);margin-left:6px">{{ $listing->price_unit_label }}</span></div>
                    @if ($listing->total_price)
                        <div class="sub">≈ ${{ number_format($listing->total_price) }} for the full {{ number_format($listing->points) }}-point balance</div>
                    @elseif ($listing->mode === 'own')
                        <div class="sub">Asking price &middot; owner will consider offers</div>
                    @else
                        <div class="sub">Set by the owner &middot; Listora adds nothing on top</div>
                    @endif
                </div>

                <div class="owner-top">
                    <span class="avatar">{{ Str::of($listing->owner_name)->explode(' ')->map(fn ($p) => Str::substr($p, 0, 1))->take(2)->implode('') }}</span>
                    <span>
                        <span class="nm">{{ $listing->owner_name }}</span><br>
                        <span class="sub">Owner since {{ $listing->owner_since }} &middot; replies {{ $listing->response_time }}</span>
                    </span>
                </div>

                <form method="POST" action="{{ route('inquiries.store', $listing) }}">
                    @csrf

                    <div class="field">
                        <label for="name">Your name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name') <span class="err">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email') <span class="err">{{ $message }}</span> @enderror
                    </div>

                    @if ($listing->mode === 'rent')
                        <div class="frow">
                            <div class="field">
                                <label for="arrive">Arrive</label>
                                <input type="date" id="arrive" name="arrive" value="{{ old('arrive') }}">
                            </div>
                            <div class="field">
                                <label for="depart">Depart</label>
                                <input type="date" id="depart" name="depart" value="{{ old('depart') }}">
                                @error('depart') <span class="err">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    <div class="field">
                        <label for="message">Message to {{ Str::before($listing->owner_name, ' ') }}</label>
                        <textarea id="message" name="message" required
                                  placeholder="Tell the owner what you're looking for and when.">{{ old('message', 'Hello — I saw your listing '.$listing->reference.' and I have a few questions.') }}</textarea>
                        @error('message') <span class="err">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Message the owner</button>
                </form>

                <div class="trust">
                    <div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3.5v5c0 5-3.4 8.8-8 10-4.6-1.2-8-5-8-10v-5L12 3z"/><path d="M9 12l2 2 4-4"/></svg>
                        <span>Ownership documents checked by our team before this listing published.</span>
                    </div>
                    <div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                        <span>Your message goes only to this owner. We never resell inquiries as leads.</span>
                    </div>
                    <div>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <span>Listora takes no commission. Whatever you agree is between the two of you.</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

@if ($similar->count())
<section class="band">
    <div class="wrap">
        <div class="sec-head reveal">
            <span class="eyebrow">You might also like</span>
            <h2>Similar listings</h2>
        </div>
        <div class="grid g3">
            @foreach ($similar as $s)
                <x-listing-card :listing="$s"/>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
