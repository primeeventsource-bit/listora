@extends('layouts.app')

@section('title', $user->name.' — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap-sm">
        <span class="eyebrow"><a href="{{ route('admin.users.index') }}">Users</a></span>
        <h1>{{ $user->name }}</h1>
        <p>
            <span class="pill">{{ $user->role?->label() }}</span>
            @if (! $user->isActive())<span class="pill pill-off">Deactivated</span>@endif
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap-sm">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif

        <dl class="detail-list">
            <dt>Email</dt><dd>{{ $user->email }}
                @if (! $user->email_verified_at)<span class="muted"> · unverified</span>@endif</dd>
            <dt>Phone</dt><dd>{{ $user->phone ?: '—' }}</dd>
            <dt>Joined</dt><dd>{{ $user->created_at?->format('j M Y') }}</dd>
            <dt>Last seen</dt><dd>{{ $user->last_login_at?->format('j M Y, H:i') ?? 'never' }}</dd>
            @if ($user->deactivated_at)
                <dt>Deactivated</dt><dd>{{ $user->deactivated_at->format('j M Y, H:i') }}</dd>
            @endif
        </dl>

        <div class="row-actions" style="margin:24px 0">
            @can('users.edit')
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline btn-sm">Edit</a>
            @endcan
            @can('users.view')
                <a href="{{ route('admin.users.login-history', $user) }}" class="btn btn-outline btn-sm">Login history</a>
            @endcan
            @can('users.deactivate')
                @if ($user->isActive())
                    <form method="POST" action="{{ route('admin.users.deactivate', $user) }}">
                        @csrf<button class="btn btn-outline btn-sm">Deactivate</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">
                        @csrf<button class="btn btn-outline btn-sm">Reactivate</button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
</section>

@endsection
