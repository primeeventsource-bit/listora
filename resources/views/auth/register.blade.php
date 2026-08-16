@extends('layouts.guest')

@section('title', 'Create an account — Listora')

@section('content')

<h1 class="auth-title">Create your account</h1>
<p class="auth-sub muted">
    You need an account to advertise a listing and answer inquiries. Browsing
    and contacting owners never requires one.
</p>

@if ($errors->any())
    <div class="notice error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="frow">
        <div class="field">
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                   required autofocus autocomplete="given-name" maxlength="120">
        </div>
        <div class="field">
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                   required autocomplete="family-name" maxlength="120">
        </div>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               required autocomplete="username" maxlength="255">
    </div>

    <div class="field">
        <label for="phone">Phone</label>
        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
               required autocomplete="tel" maxlength="32">
        <span class="field-hint">
            Used to verify ownership when you list. It is never shown on a listing.
        </span>
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary btn-block">Create account</button>
</form>

<p class="auth-alt muted">
    By creating an account you agree to our
    <a href="{{ route('legal.tos') }}">Terms</a> and
    <a href="{{ route('legal.privacy') }}">Privacy Policy</a>.
</p>

<p class="auth-alt muted">
    Already have an account? <a href="{{ route('login') }}">Sign in</a>
</p>

@endsection
