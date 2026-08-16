@extends('layouts.app')

@section('title', 'Your profile — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow">Your account</span>
        <h1>Profile</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">

        @if (session('status') === 'profile-updated')
            <div class="notice">Saved.</div>
        @endif

        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="stack-form">
            @csrf
            @method('PATCH')

            <div class="frow">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name"
                           value="{{ old('first_name', $user->first_name) }}" maxlength="120">
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name"
                           value="{{ old('last_name', $user->last_name) }}" maxlength="120">
                </div>
            </div>

            <div class="field">
                <label for="name">Display name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255">
                <span class="field-hint">Shown on your listings as the owner name.</span>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255">
                <span class="field-hint">
                    Where inquiries and offers reach you. Changing it means verifying the new
                    address before we'll send anything to it.
                </span>
            </div>

            <div class="field">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="40">
                <span class="field-hint">Used for ownership verification. Never shown on a listing.</span>
            </div>

            <button type="submit" class="btn btn-primary">Save changes</button>
        </form>

        <hr class="rule">

        <div class="danger-zone">
            <h2>Close your account</h2>
            <p class="muted">
                This deletes your account and cannot be undone. Live listings are taken down.
                If you only want a break, pause your listings instead — the term keeps running
                either way, so closing early forfeits it.
            </p>

            <form method="POST" action="{{ route('profile.destroy') }}"
                  onsubmit="return confirm('Delete your account permanently? This cannot be undone.')">
                @csrf
                @method('DELETE')

                <div class="field">
                    <label for="delete_password">Confirm your password</label>
                    <input type="password" id="delete_password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-danger">Delete my account</button>
            </form>
        </div>

    </div>
</section>

@endsection
