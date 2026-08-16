@extends('layouts.guest')

@section('title', 'Verify your email — Listora')

@section('content')

<h1 class="auth-title">Confirm your email</h1>
<p class="auth-sub muted">
    We've sent a link to your address. Click it and you're done — it's how we
    make sure inquiries about your listing actually reach you.
</p>

@if (session('status') === 'verification-link-sent')
    <div class="notice">
        A fresh link is on its way to the address you registered with.
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="btn btn-primary btn-block">Send it again</button>
</form>

<form method="POST" action="{{ route('logout') }}" class="auth-alt">
    @csrf
    <button type="submit" class="btn-link">Sign out</button>
</form>

<p class="auth-alt muted">
    Link never arrived? Check your spam folder, then
    <a href="{{ route('help.index') }}#ask">tell us</a> and we'll sort it out.
</p>

@endsection
