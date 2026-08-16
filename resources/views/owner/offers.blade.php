@extends('layouts.app')

@section('title', 'Offers and inquiries — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Your account</span>
        <h1>Offers and inquiries</h1>
        <p>
            Accepting shares your contact details with the sender and closes the record here.
            It doesn't reserve dates or move any money — the two of you arrange that directly.
        </p>
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

        @if ($offers->isEmpty())
            <p class="muted">Nothing yet. New inquiries and offers land here and in your email.</p>
        @else
            <div class="offer-list">
                @foreach ($offers as $offer)
                    <article class="offer-card {{ $offer->isActionable() ? 'is-open' : '' }}">
                        <header>
                            <div>
                                <span class="pill">{{ $offer->kind?->label() }}</span>
                                <span class="pill">{{ $offer->status?->label() }}</span>
                                <code>{{ $offer->reference }}</code>
                            </div>
                            <span class="muted">{{ $offer->created_at?->diffForHumans() }}</span>
                        </header>

                        <h3>
                            @if ($offer->listing)
                                <a href="{{ route('listings.show', $offer->listing) }}">{{ $offer->listing->title }}</a>
                            @else
                                <span class="muted">Listing removed</span>
                            @endif
                        </h3>

                        @if ($offer->offer_amount_cents !== null)
                            <p class="offer-amount">{{ $offer->amount_formatted }}</p>
                        @endif

                        @if ($offer->arrive && $offer->depart)
                            <p class="muted">
                                {{ $offer->arrive->format('j M Y') }} – {{ $offer->depart->format('j M Y') }}
                                @if ($offer->guests) · {{ $offer->guests }} guests @endif
                            </p>
                        @endif

                        <p class="offer-message">{{ $offer->message }}</p>

                        <footer>
                            @if ($offer->isActionable())
                                <div class="offer-actions">
                                    <form method="POST" action="{{ route('owner.offers.accept', $offer) }}">
                                        @csrf
                                        <button class="btn btn-primary btn-sm">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('owner.offers.decline', $offer) }}">
                                        @csrf
                                        <button class="btn btn-outline btn-sm">Decline</button>
                                    </form>
                                </div>
                                <span class="muted">
                                    Expires {{ $offer->expires_at?->diffForHumans() }} if you don't reply.
                                </span>
                            @else
                                {{-- Contact details appear only once the owner has accepted —
                                     before that the sender's address is not the owner's to keep. --}}
                                @if ($offer->status?->value === 'accepted')
                                    <div class="offer-contact">
                                        <strong>{{ $offer->name }}</strong>
                                        <a href="mailto:{{ $offer->email }}">{{ $offer->email }}</a>
                                        @if ($offer->phone) <span>{{ $offer->phone }}</span> @endif
                                    </div>
                                @else
                                    <span class="muted">Closed — {{ $offer->status?->label() }}.</span>
                                @endif
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>

            <div class="pager">{{ $offers->links() }}</div>
        @endif
    </div>
</section>

@endsection
