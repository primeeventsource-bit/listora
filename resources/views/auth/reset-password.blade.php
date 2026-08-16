@extends('layouts.guest')

@section('title', 'Choose a new password — Listora')

@section('content')

<h1 class="auth-title">Choose a new password</h1>
<p class="auth-sub muted">Set it once and you're back in.</p>

@if ($errors->any())
    <div class="notice error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('password.store') }}">
    @csrf

    {{-- The token comes from the emailed link and is what proves this request
         is the one we sent. --}}
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="{{ old('email', $request->email) }}"
               required autofocus autocomplete="username">
    </div>

    <div class="field">
        <label for="password">New password</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">
    </div>

    <div class="field">
        <label for="password_confirmation">Confirm new password</label>
        <input type="password" id="password_confirmation" name="password_confirmation"
               required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary btn-block">Save new password</button>
</form>

@endsection
