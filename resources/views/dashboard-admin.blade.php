@extends('layouts.app')

@section('title', 'Operations — Listora')

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>What needs you today</h1>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        <div class="stat-row">
            <a href="{{ route('admin.drafts.index') }}" class="stat {{ $draftsAwaiting > 0 ? 'stat-urgent' : '' }}">
                <span class="stat-n">{{ number_format($draftsAwaiting) }}</span>
                <span class="stat-l">Awaiting verification</span>
            </a>
            <a href="{{ route('admin.drafts.index', ['status' => 'verified']) }}" class="stat">
                <span class="stat-n">{{ number_format($draftsVerified) }}</span>
                <span class="stat-l">Verified, ready to publish</span>
            </a>
            <a href="{{ route('admin.listings.index') }}" class="stat">
                <span class="stat-n">{{ number_format($listingsLive) }}</span>
                <span class="stat-l">Live listings</span>
            </a>
            <a href="{{ route('admin.listings.index') }}" class="stat {{ $listingsExpiring > 0 ? 'stat-warn' : '' }}">
                <span class="stat-n">{{ number_format($listingsExpiring) }}</span>
                <span class="stat-l">Terms ending soon</span>
            </a>
            <a href="{{ route('admin.offers.index') }}" class="stat">
                <span class="stat-n">{{ number_format($offersOpen) }}</span>
                <span class="stat-l">Open offers</span>
            </a>
        </div>
    </div>
</section>

<section class="pad-sm">
    <div class="wrap">
        <div class="section-head">
            <h2>Review queue</h2>
            <p class="muted">
                Drafts are invisible to the public until ownership is verified — that is the
                promise every plan makes, so nothing here is waiting on the site, it is waiting
                on us.
            </p>
        </div>

        @if ($recentDrafts->isEmpty())
            <p class="muted">Nothing outstanding. The queue is clear.</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Owner</th>
                            <th>What</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentDrafts as $draft)
                            <tr>
                                <td><code>{{ $draft->reference }}</code></td>
                                <td>{{ $draft->owner_name }}<br><span class="muted">{{ $draft->owner_email }}</span></td>
                                <td>{{ $draft->resort_name ?: $draft->club_name ?: $draft->city ?: '—' }}</td>
                                <td>{{ $draft->plan?->label() ?? '—' }}</td>
                                <td><span class="pill">{{ $draft->status?->label() }}</span></td>
                                <td class="muted">{{ $draft->created_at?->diffForHumans() }}</td>
                                <td><a href="{{ route('admin.drafts.show', $draft) }}" class="btn btn-outline btn-sm">Review</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p style="margin-top:22px">
                <a href="{{ route('admin.drafts.index') }}" class="btn btn-outline">See the whole queue</a>
            </p>
        @endif
    </div>
</section>

@endsection
