@extends('layouts.app')

@section('title', 'Inquiries — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Your account</span>
        <h1>Inquiries</h1>
        <p>Messages about your listings. Reply straight from your email — we stay out of it.</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if ($inquiries->isEmpty())
            <p class="muted">No messages yet.</p>
        @else
            <div class="offer-list">
                @foreach ($inquiries as $inquiry)
                    <article class="offer-card">
                        <header>
                            <div>
                                <strong>{{ $inquiry->name }}</strong>
                                <a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a>
                                @if ($inquiry->phone) <span class="muted">{{ $inquiry->phone }}</span> @endif
                            </div>
                            <span class="muted">{{ $inquiry->created_at?->diffForHumans() }}</span>
                        </header>

                        <h3>
                            @if ($inquiry->listing)
                                <a href="{{ route('listings.show', $inquiry->listing) }}">{{ $inquiry->listing->title }}</a>
                            @else
                                <span class="muted">Listing removed</span>
                            @endif
                        </h3>

                        @if ($inquiry->arrive && $inquiry->depart)
                            <p class="muted">
                                {{ $inquiry->arrive->format('j M Y') }} – {{ $inquiry->depart->format('j M Y') }}
                                @if ($inquiry->guests) · {{ $inquiry->guests }} occupants @endif
                            </p>
                        @endif

                        <p class="offer-message">{{ $inquiry->message }}</p>

                        <footer>
                            <a href="mailto:{{ $inquiry->email }}?subject={{ rawurlencode('Re: '.($inquiry->listing->title ?? 'your enquiry')) }}"
                               class="btn btn-primary btn-sm">Reply by email</a>
                        </footer>
                    </article>
                @endforeach
            </div>

            <div class="pager">{{ $inquiries->links() }}</div>
        @endif
    </div>
</section>

@endsection
