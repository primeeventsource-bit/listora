@extends('layouts.app')

@section('title', 'Feature flags — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Settings</span>
        <h1>Feature flags</h1>
        <p>
            A missing flag resolves to on, so a feature that used to work never dark-ships just
            because a row is absent.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif

        @if ($flags->isEmpty())
            <p class="muted">No flags seeded yet.</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Flag</th><th>State</th><th>Scope</th><th>Rollout</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($flags as $flag)
                            <tr>
                                <td>
                                    <code>{{ $flag->key }}</code>
                                    @if ($flag->description)<br><span class="muted">{{ $flag->description }}</span>@endif
                                </td>
                                <td>
                                    <span class="pill {{ $flag->enabled ? '' : 'pill-off' }}">
                                        {{ $flag->enabled ? 'On' : 'Off' }}
                                    </span>
                                </td>
                                <td class="muted">{{ $flag->scope ?: 'global' }} {{ $flag->scope_value }}</td>
                                <td class="muted">{{ $flag->rollout_pct === null ? '100%' : $flag->rollout_pct.'%' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.settings.flags.update', $flag->key) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="enabled" value="{{ $flag->enabled ? 0 : 1 }}">
                                        <button class="btn btn-outline btn-sm">
                                            Turn {{ $flag->enabled ? 'off' : 'on' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@endsection
