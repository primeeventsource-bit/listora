{{--
    Advertiser dashboard.

    Rebuilt on layouts.member. This screen used to render inside the public
    marketing layout, so a paying advertiser checking their listings saw the
    same header that sells plans to strangers.

    Ordered by what an advertiser opens this page to find out: is my
    advertising running, is anyone interested, and what is it doing. The
    advertising program panel answers the first question explicitly rather
    than leaving someone to infer it from a status pill - it names the plan,
    when it went live, and when the term ends.
--}}
@extends('layouts.member')

@section('title', 'Your advertising')
@section('crumb', 'Dashboard')

@section('content')

@php
    $live = $listings->where('status', \App\Enums\ListingStatus::Active);
    $totalViews = $listings->sum('views');
    $totalInquiries = $listings->sum('inquiries_count');
    $totalOffers = $listings->sum('offers_count');
@endphp

<div class="c-head">
    <div>
        <h1 class="c-head__t">Your advertising</h1>
        <p class="c-head__s">
            @if ($listings->isEmpty())
                Nothing advertised yet.
            @else
                {{ $live->count() }} of {{ $listings->count() }} {{ Str::plural('listing', $listings->count()) }} live
            @endif
        </p>
    </div>
    <div class="c-head__actions">
        <a href="{{ route('owner.listings.index') }}" class="c-btn c-btn--sm">My listings</a>
    </div>
</div>

@if ($expiringSoon->isNotEmpty())
    <div class="c-note">
        <strong>{{ $expiringSoon->count() }} {{ Str::plural('listing', $expiringSoon->count()) }}</strong>
        {{ $expiringSoon->count() === 1 ? 'is' : 'are' }} near the end of the advertising term.
        Renew to keep {{ $expiringSoon->count() === 1 ? 'it' : 'them' }} visible &mdash;
        once the term ends the listing stops showing on the site, but nothing is deleted.
    </div>
@endif

{{--
    No empty state here on purpose. DashboardController::show() sends a viewer
    with no listings to the traveler dashboard instead, so this view is only
    ever reached with at least one. A "nothing here yet" branch would be
    unreachable code that reads like a handled case.
--}}
    <div class="c-tiles">
        <div class="c-tile">
            <span class="c-tile__l">Live listings</span>
            <span class="c-tile__v">{{ number_format($live->count()) }}</span>
        </div>
        <a href="{{ route('owner.inquiries.index') }}" class="c-tile {{ $unreadInquiries > 0 ? 'c-tile--urgent' : '' }}">
            <span class="c-tile__l">Unread inquiries</span>
            <span class="c-tile__v">{{ number_format($unreadInquiries) }}</span>
        </a>
        <a href="{{ route('owner.offers.index') }}" class="c-tile {{ $openOffers > 0 ? 'c-tile--urgent' : '' }}">
            <span class="c-tile__l">Open offers</span>
            <span class="c-tile__v">{{ number_format($openOffers) }}</span>
        </a>
        <div class="c-tile">
            <span class="c-tile__l">Listing views</span>
            <span class="c-tile__v">{{ number_format($totalViews) }}</span>
            <span class="c-tile__m">{{ number_format($totalInquiries) }} inquiries &middot; {{ number_format($totalOffers) }} offers</span>
        </div>
    </div>

    @if ($drafts->isNotEmpty())
        <div class="c-card" style="margin-bottom:16px">
            <div class="c-card__h">
                <h2 class="c-card__t">In review</h2>
            </div>
            <div class="c-card__b--flush c-scroll">
                <table class="c-table">
                    <thead>
                        <tr>
                            <th scope="col">Reference</th>
                            <th scope="col">Property</th>
                            <th scope="col">Program</th>
                            <th scope="col">Status</th>
                            <th scope="col">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($drafts as $draft)
                            <tr>
                                <td><strong>{{ $draft->reference }}</strong></td>
                                <td>{{ $draft->resort_name ?: $draft->club_name ?: $draft->city ?: '—' }}</td>
                                <td>{{ $draft->plan?->label() ?? '—' }}</td>
                                <td><span class="c-pill c-pill--pending">{{ $draft->status?->label() }}</span></td>
                                <td class="c-table__muted">{{ $draft->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="c-card__b" style="border-top:1px solid var(--c-line)">
                <p style="margin:0;font-size:13px;color:var(--c-ink-2)">
                    Listings stay private until our team has verified ownership. That check is what
                    every plan promises visitors, so it happens before anything goes live.
                </p>
            </div>
        </div>
    @endif

    @if ($listings->isNotEmpty())
        <div class="c-card" style="margin-bottom:16px">
            <div class="c-card__h">
                <h2 class="c-card__t">Advertising program</h2>
                <a href="{{ route('owner.listings.index') }}" class="c-card__link">Manage</a>
            </div>
            <div class="c-card__b--flush c-scroll">
                <table class="c-table">
                    <thead>
                        <tr>
                            <th scope="col">Property</th>
                            <th scope="col">Program</th>
                            <th scope="col">Status</th>
                            <th scope="col">Live since</th>
                            <th scope="col">Term ends</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listings as $listing)
                            @php
                                $isLive = $listing->status === \App\Enums\ListingStatus::Active;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('owner.listings.edit', $listing) }}"><strong>{{ $listing->title }}</strong></a>
                                    <div class="c-table__muted">{{ $listing->reference }}</div>
                                </td>
                                <td>{{ $listing->plan?->label() ?? '—' }}</td>
                                <td>
                                    <span class="c-pill {{ $isLive ? 'c-pill--live' : 'c-pill--off' }}">
                                        {{ $listing->status?->label() ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="c-table__muted">{{ $listing->published_at?->format('j M Y') ?? '—' }}</td>
                                <td class="c-table__muted">{{ $listing->expires_at?->format('j M Y') ?? 'No end date' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="c-head">
            <div>
                <h2 class="c-head__t" style="font-size:17px">Advertising performance</h2>
                <p class="c-head__s">How each listing is doing.</p>
            </div>
        </div>

        <div class="c-lcards">
            @foreach ($listings as $listing)
                @php
                    $photo = is_array($listing->photos) ? ($listing->photos[0] ?? null) : null;
                    $isLive = $listing->status === \App\Enums\ListingStatus::Active;
                @endphp
                <a href="{{ route('owner.listings.edit', $listing) }}" class="c-lcard">
                    <div class="c-lcard__img" @if ($photo) style="background-image:url('{{ $photo }}')" @endif>
                        <span class="c-pill {{ $isLive ? 'c-pill--live' : 'c-pill--off' }} c-lcard__pill">
                            {{ $listing->status?->label() ?? 'Unknown' }}
                        </span>
                    </div>
                    <div class="c-lcard__b">
                        <h3 class="c-lcard__t">{{ $listing->title }}</h3>
                        <span class="c-lcard__loc">{{ collect([$listing->city, $listing->state])->filter()->implode(', ') ?: $listing->resort_name }}</span>
                        <div class="c-lcard__stats">
                            <span class="c-lcard__stat">
                                <span class="c-lcard__sv">{{ number_format($listing->views) }}</span>
                                <span class="c-lcard__sl">Views</span>
                            </span>
                            <span class="c-lcard__stat">
                                <span class="c-lcard__sv">{{ number_format($listing->inquiries_count) }}</span>
                                <span class="c-lcard__sl">Inquiries</span>
                            </span>
                            <span class="c-lcard__stat">
                                <span class="c-lcard__sv">{{ number_format($listing->offers_count) }}</span>
                                <span class="c-lcard__sl">Offers</span>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

@endsection
