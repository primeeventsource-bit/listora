@extends('layouts.app')

@section('title', 'Review queue — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>Listing review queue</h1>
        <p>
            Drafts are invisible to the public until ownership is verified. Nothing here is
            waiting on the site — it's waiting on us.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif

        <div class="filter-bar">
            <a href="{{ route('admin.drafts.index') }}"
               class="chip {{ $status === 'open' ? 'is-active' : '' }}">
                Outstanding ({{ $counts['awaiting_verification'] + $counts['verified'] }})
            </a>
            @foreach ($statuses as $s)
                <a href="{{ route('admin.drafts.index', ['status' => $s->value]) }}"
                   class="chip {{ $status === $s->value ? 'is-active' : '' }}">{{ $s->label() }}</a>
            @endforeach
            <a href="{{ route('admin.drafts.index', ['status' => 'all']) }}"
               class="chip {{ $status === 'all' ? 'is-active' : '' }}">All</a>
        </div>

        @if ($drafts->isEmpty())
            <p class="muted">Nothing here. The queue is clear.</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr><th>Reference</th><th>Owner</th><th>What</th><th>Plan</th><th>Status</th><th>Submitted</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($drafts as $draft)
                            <tr>
                                <td><code>{{ $draft->reference }}</code></td>
                                <td>
                                    {{ $draft->owner_name }}<br>
                                    <span class="muted">{{ $draft->owner_email }}</span>
                                </td>
                                <td>
                                    {{ $draft->title ?: $draft->resort_name ?: $draft->club_name ?: '—' }}<br>
                                    <span class="muted">{{ $draft->city }} · {{ $draft->kind }}</span>
                                </td>
                                <td>{{ $draft->plan?->label() ?? '—' }}</td>
                                <td><span class="pill">{{ $draft->status?->label() }}</span></td>
                                <td class="muted">{{ $draft->created_at?->diffForHumans() }}</td>
                                <td><a href="{{ route('admin.drafts.show', $draft) }}" class="btn btn-outline btn-sm">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pager">{{ $drafts->links() }}</div>
        @endif
    </div>
</section>

@endsection
