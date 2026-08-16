@extends('layouts.app')

@section('title', 'Get new listings by email — Listora')

@section('content')

<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow">Newsletter</span>
        <h1>New listings, once a week</h1>
        <p>
            A short email with what's newly published. No daily digest, no partner offers, and
            we never pass your address to anyone.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">

        @if (session('subscribed'))
            <div class="notice">
                <p><strong>You're on the list.</strong> The next one goes out on Thursday.</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('signup.store') }}" class="stack-form">
            @csrf

            <div class="field">
                <label for="full_name">Name</label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}"
                       maxlength="160" required>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       maxlength="190" required>
            </div>

            <div class="field">
                <label for="phone">Phone <span class="muted">(optional)</span></label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" maxlength="40">
            </div>

            <button type="submit" class="btn btn-primary">Subscribe</button>
        </form>

        <p class="muted" style="margin-top:22px">
            {{-- Distinct from /register, and worth saying — people confuse the two. --}}
            This is the mailing list, not an account. To advertise a listing you'll want to
            <a href="{{ route('register') }}">create an account</a> instead. Unsubscribe from any
            email, any time.
        </p>

    </div>
</section>

@endsection
