@extends('layouts.console')

@section('title', 'New user — Listora')

@section('content')


<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow"><a href="{{ route('admin.users.index') }}">Users</a></span>
        <h1>Create a user</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">
        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}" class="stack-form">
            @csrf

            <div class="frow">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                </div>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="field">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            </div>

            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @unless ($canGrantAdmin)
                    {{-- Explains an absence rather than silently hiding options: a
                         user may never create a role at or above their own level. --}}
                    <span class="field-hint">
                        Admin roles aren't listed because you can't grant a role at or above
                        your own level.
                    </span>
                @endunless
            </div>

            <button type="submit" class="btn btn-primary">Create user</button>
        </form>
    </div>
</section>

@endsection
