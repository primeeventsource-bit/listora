@extends('layouts.guest')

@section('title', 'Sign in — Listora')

@section('content')

<h1 class="auth-title">Welcome back</h1>
<p class="auth-sub muted">Sign in to manage your listings and answer inquiries.</p>

@if (session('status'))
    <div class="notice">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="notice error">
        {{-- Deliberately one message, not a field-by-field breakdown. Telling
             someone which half of the pair was wrong is an account-enumeration
             oracle. --}}
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               required autofocus autocomplete="username">
    </div>

    <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               required autocomplete="current-password">
    </div>

    <div class="auth-row">
        <label class="checkline">
            <input type="checkbox" name="remember">
            <span>Remember me</span>
        </label>

        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
        @endif
    </div>

    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
</form>

<p class="auth-alt muted">
    Don't have an account? <a href="{{ route('register') }}">Create one</a> —
    you only need one to advertise a listing. Browsing and contacting owners
    works without signing up.
</p>

@endsection
