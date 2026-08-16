@extends('layouts.app')

@section('title', 'Your listings — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Your account</span>
        <h1>Your listings</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))
            <div class="notice">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice error">{{ $errors->first() }}</div>
        @endif

        @if ($listings->isEmpty())
            <p class="muted">
                Nothing here yet.
                <a href="{{ route('list.create') }}">Advertise a property, points package, or week</a>.
            </p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Listing</th><th>Status</th><th>Views</th>
                            <th>Inquiries</th><th>Offers</th><th>Term ends</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listings as $listing)
                            <tr>
                                <td>
                                    <a href="{{ route('listings.show', $listing) }}">{{ $listing->title }}</a>
                                    <br><span class="muted">{{ $listing->location }} · {{ $listing->kind_label }}</span>
                                </td>
                                <td>
                                    <span class="pill">{{ $listing->status?->label() }}</span>
                                    @if ($listing->hasExpired())
                                        <br><span class="muted">term ended</span>
                                    @endif
                                </td>
                                <td>{{ number_format($listing->views) }}</td>
                                <td>{{ number_format($listing->inquiries_count) }}</td>
                                <td>{{ number_format($listing->offers_count) }}</td>
                                <td class="muted">{{ $listing->expires_at?->format('j M Y') ?? '—' }}</td>
                                <td class="row-actions">
                                    <a href="{{ route('owner.listings.edit', $listing) }}" class="btn btn-outline btn-sm">Edit</a>

                                    @if ($listing->status?->isPublic())
                                        <form method="POST" action="{{ route('owner.listings.pause', $listing) }}">
                                            @csrf
                                            <button class="btn-link">Pause</button>
                                        </form>
                                    @elseif ($listing->status?->isRenewable() && ! $listing->hasExpired())
                                        <form method="POST" action="{{ route('owner.listings.resume', $listing) }}">
                                            @csrf
                                            <button class="btn-link">Resume</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pager">{{ $listings->links() }}</div>
        @endif
    </div>
</section>

@endsection
