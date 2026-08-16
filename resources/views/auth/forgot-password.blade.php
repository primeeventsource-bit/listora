@extends('layouts.guest')

@section('title', 'Reset your password — Listora')

@section('content')

<h1 class="auth-title">Reset your password</h1>
<p class="auth-sub muted">
    Give us the email on your account and we'll send a link to set a new password.
</p>

@if (session('status'))
    <div class="notice">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="notice error">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               required autofocus autocomplete="username">
    </div>

    <button type="submit" class="btn btn-primary btn-block">Email me a reset link</button>
</form>

<p class="auth-alt muted">
    {{-- Said plainly so nobody sits refreshing an inbox. The response is the
         same whether or not the address exists, which is deliberate: telling
         someone "no account with that email" confirms who does have one. --}}
    We send the link to that address if an account exists. Check your spam
    folder before trying again.
</p>

<p class="auth-alt muted">
    <a href="{{ route('login') }}">Back to sign in</a>
</p>

@endsection
