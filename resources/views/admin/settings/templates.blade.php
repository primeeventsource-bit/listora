@extends('layouts.console')

@section('title', 'Message templates — Listora')

@section('content')


<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Settings</span>
        <h1>Message templates</h1>
        <p>
            Templates are versioned. Editing one creates a new version rather than mutating the
            old — a message already sent must stay readable exactly as it was sent.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
        @if ($errors->any())
            <div class="notice error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="filter-bar">
            <a href="{{ route('admin.settings.templates', ['channel' => 'email']) }}"
               class="chip {{ $channel === 'email' ? 'is-active' : '' }}">Email</a>
            <a href="{{ route('admin.settings.templates', ['channel' => 'sms']) }}"
               class="chip {{ $channel === 'sms' ? 'is-active' : '' }}">SMS</a>
        </div>

        @if ($templates->isEmpty())
            <p class="muted">No {{ $channel }} templates yet.</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Key</th><th>Name</th><th>Version</th><th>Active</th><th>Updated</th></tr></thead>
                    <tbody>
                        @foreach ($templates as $template)
                            <tr>
                                <td><code>{{ $template->key }}</code></td>
                                <td>{{ $template->name }}</td>
                                <td>v{{ $template->version }}</td>
                                <td>
                                    <span class="pill {{ $template->active ? '' : 'pill-off' }}">
                                        {{ $template->active ? 'Active' : 'Superseded' }}
                                    </span>
                                </td>
                                <td class="muted">{{ $template->updated_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@endsection
