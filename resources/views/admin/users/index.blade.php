@extends('layouts.app')

@section('title', 'Users — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>Users</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif

        <div class="filter-bar">
            <span class="muted">
                {{ $counts['total'] ?? 0 }} total ·
                {{ $counts['active'] ?? 0 }} active ·
                {{ $counts['deactivated'] ?? 0 }} deactivated
            </span>
            @can('users.create')
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">New user</a>
            @endcan
        </div>

        <form method="GET" class="filter-form">
            <input type="search" name="q" value="{{ $q }}" placeholder="Name or email…">
            <select name="role">
                <option value="">All roles</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status">
                <option value="">Any status</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="deactivated" @selected($status === 'deactivated')>Deactivated</option>
            </select>
            <button class="btn btn-outline btn-sm">Filter</button>
        </form>

        @if ($users->isEmpty())
            <p class="muted">No users match.</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last seen</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></td>
                                <td class="muted">{{ $user->email }}</td>
                                <td>{{ $user->role?->label() }}</td>
                                <td>
                                    @if ($user->isActive())
                                        <span class="pill">Active</span>
                                    @else
                                        <span class="pill pill-off">Deactivated</span>
                                    @endif
                                </td>
                                <td class="muted">{{ $user->last_login_at?->diffForHumans() ?? 'never' }}</td>
                                <td class="row-actions">
                                    @can('users.edit')
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline btn-sm">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pager">{{ $users->links() }}</div>
        @endif
    </div>
</section>

@endsection
