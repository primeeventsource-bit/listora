@extends('layouts.app')

@section('title', 'Your offers — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Your account</span>
        <h1>Hello, {{ auth()->user()->firstNameOrName() }}</h1>
        <p>Everything you've sent an owner, and where each one stands.</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if ($offers->isEmpty())
            <p class="muted">
                You haven't contacted an owner yet.
                <a href="{{ route('listings.index') }}">Browse the listings</a> and send an
                inquiry or an offer — you'll see the replies here and in your inbox.
            </p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Reference</th><th>Listing</th><th>Type</th><th>Amount</th><th>Status</th><th>Sent</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($offers as $offer)
                            <tr>
                                <td><code>{{ $offer->reference }}</code></td>
                                <td>
                                    @if ($offer->listing)
                                        <a href="{{ $offer->listing->publicUrl() }}">{{ $offer->listing->title }}</a>
                                    @else
                                        <span class="muted">Listing no longer available</span>
                                    @endif
                                </td>
                                <td>{{ $offer->kind?->label() }}</td>
                                <td>{{ $offer->amount_formatted ?? '—' }}</td>
                                <td>
                                    <span class="pill">{{ $offer->status?->label() }}</span>
                                    @if ($offer->status?->isOpen() && $offer->expires_at)
                                        <br><span class="muted">expires {{ $offer->expires_at->diffForHumans() }}</span>
                                    @endif
                                </td>
                                <td class="muted">{{ $offer->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="muted" style="margin-top:22px">
                When an owner accepts, we share contact details and the two of you arrange
                dates and payment directly. Listora is never part of that arrangement, and will
                never ask you to send funds to us.
            </p>
        @endif
    </div>
</section>

@endsection
