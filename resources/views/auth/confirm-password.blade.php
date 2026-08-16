@extends('layouts.guest')

@section('title', 'Confirm your password — Listora')

@section('content')

<h1 class="auth-title">Confirm your password</h1>
<p class="auth-sub muted">
    You're about to do something sensitive. Enter your password to continue.
</p>

@if ($errors->any())
    <div class="notice error">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               required autofocus autocomplete="current-password">
    </div>

    <button type="submit" class="btn btn-primary btn-block">Confirm</button>
</form>

@endsection
