@extends('layouts.console')

@section('title', 'Edit '.$user->name.' — Listora')

@section('content')


<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow"><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></span>
        <h1>Edit user</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if ($isSelf)
            <div class="notice amber">
                This is your own account. You can't change your own role or deactivate yourself —
                that's what stops an admin locking the last admin out.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="stack-form">
            @csrf
            @method('PATCH')

            <div class="frow">
                <div class="field">
                    <label for="first_name">First name</label>
                    <input type="text" id="first_name" name="first_name"
                           value="{{ old('first_name', $user->first_name) }}" required>
                </div>
                <div class="field">
                    <label for="last_name">Last name</label>
                    <input type="text" id="last_name" name="last_name"
                           value="{{ old('last_name', $user->last_name) }}" required>
                </div>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="field">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            </div>

            @unless ($isSelf)
                <div class="field">
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $user->role?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @unless ($canGrantAdmin)
                        <span class="field-hint">
                            Admin roles aren't listed — you can't grant a role at or above your own level.
                        </span>
                    @endunless
                </div>
            @endunless

            <button type="submit" class="btn btn-primary">Save changes</button>
        </form>
    </div>
</section>

@endsection
