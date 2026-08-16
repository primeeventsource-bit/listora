@extends('layouts.app')

@section('title', 'Roles &amp; permissions — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>Roles &amp; permissions</h1>
        <p>
            Every admin screen is gated on a specific permission, not on a blanket admin flag —
            so a role can be given exactly one module and nothing else.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="notice error">{{ $errors->first() }}</div>@endif

        @can('roles.create')
            <div class="filter-bar">
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">New role</a>
            </div>
        @endcan

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Role</th><th>Key</th><th>Level</th><th>Permissions</th><th>Users</th><th></th></tr></thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                {{ $role->name }}
                                @if ($role->is_system)<span class="pill">system</span>@endif
                                @if ($role->is_super)<span class="pill">super</span>@endif
                                @if ($role->description)<br><span class="muted">{{ $role->description }}</span>@endif
                            </td>
                            <td><code>{{ $role->key }}</code></td>
                            <td>{{ $role->level }}</td>
                            <td>{{ $role->is_super ? 'all' : number_format($role->permissions_count) }}</td>
                            <td>{{ number_format($role->users_count) }}</td>
                            <td class="row-actions">
                                {{-- A role at or above the actor's own level is not
                                     theirs to edit — that is what stops an admin
                                     minting themselves a super-admin equivalent. --}}
                                @if ($role->level < $actorLevel)
                                    @can('roles.edit')
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-outline btn-sm">Edit</a>
                                    @endcan
                                    @can('roles.delete')
                                        @unless ($role->is_system)
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                                                  onsubmit="return confirm('Delete this role?')">
                                                @csrf @method('DELETE')
                                                <button class="btn-link">Delete</button>
                                            </form>
                                        @endunless
                                    @endcan
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

@endsection
