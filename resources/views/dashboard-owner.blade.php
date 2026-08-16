@extends('layouts.app')

@section('title', 'Your listings — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Your account</span>
        <h1>Hello, {{ auth()->user()->firstNameOrName() }}</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        <div class="stat-row">
            <a href="{{ route('owner.offers.index') }}" class="stat {{ $openOffers > 0 ? 'stat-urgent' : '' }}">
                <span class="stat-n">{{ number_format($openOffers) }}</span>
                <span class="stat-l">Offers awaiting your answer</span>
            </a>
            <a href="{{ route('owner.inquiries.index') }}" class="stat {{ $unreadInquiries > 0 ? 'stat-urgent' : '' }}">
                <span class="stat-n">{{ number_format($unreadInquiries) }}</span>
                <span class="stat-l">Unread inquiries</span>
            </a>
            <a href="{{ route('owner.listings.index') }}" class="stat">
                <span class="stat-n">{{ number_format($listings->count()) }}</span>
                <span class="stat-l">Listings</span>
            </a>
        </div>

        @if ($openOffers > 0)
            <p class="muted" style="margin-top:18px">
                Offers expire on a fixed clock, so silence closes them for you — worth a look
                before they lapse.
            </p>
        @endif
    </div>
</section>

@if ($expiringSoon->isNotEmpty())
    <section class="pad-sm">
        <div class="wrap">
            <div class="notice amber">
                <p><strong>Some of your advertising terms are ending soon.</strong></p>
                <ul>
                    @foreach ($expiringSoon as $listing)
                        <li>
                            <a href="{{ route('listings.show', $listing) }}">{{ $listing->title }}</a>
                            — ends {{ $listing->expires_at?->diffForHumans() }}
                        </li>
                    @endforeach
                </ul>
                <p>
                    Renewal is half price, and free on Premier.
                    <a href="{{ route('help.index') }}#ask">Ask us to renew</a> and we'll set it up.
                </p>
            </div>
        </div>
    </section>
@endif

@if ($drafts->isNotEmpty())
    <section class="pad-sm">
        <div class="wrap">
            <div class="section-head">
                <h2>Still with us for review</h2>
                <p class="muted">
                    These aren't public yet. We verify ownership before anything publishes —
                    usually one to two business days.
                </p>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Reference</th><th>What</th><th>Plan</th><th>Status</th><th>Submitted</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($drafts as $draft)
                            <tr>
                                <td><code>{{ $draft->reference }}</code></td>
                                <td>{{ $draft->title ?: $draft->resort_name ?: $draft->city ?: '—' }}</td>
                                <td>{{ $draft->plan?->label() ?? '—' }}</td>
                                <td><span class="pill">{{ $draft->status?->label() }}</span></td>
                                <td class="muted">{{ $draft->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif

<section class="pad-sm">
    <div class="wrap">
        <div class="section-head">
            <h2>Your listings</h2>
        </div>

        @if ($listings->isEmpty())
            <p class="muted">
                Nothing live yet. <a href="{{ route('list.create') }}">Advertise a property, points package, or week</a>
                — it takes about ten minutes.
            </p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Listing</th><th>Status</th><th>Views</th><th>Inquiries</th><th>Offers</th><th>Term ends</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($listings as $listing)
                            <tr>
                                <td>
                                    <a href="{{ route('listings.show', $listing) }}">{{ $listing->title }}</a>
                                    <br><span class="muted">{{ $listing->location }}</span>
                                </td>
                                <td><span class="pill">{{ $listing->status?->label() }}</span></td>
                                <td>{{ number_format($listing->views) }}</td>
                                <td>{{ number_format($listing->inquiries_count) }}</td>
                                <td>{{ number_format($listing->offers_count) }}</td>
                                <td class="muted">{{ $listing->expires_at?->format('j M Y') ?? '—' }}</td>
                                <td><a href="{{ route('owner.listings.edit', $listing) }}" class="btn btn-outline btn-sm">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@endsection
