@extends('layouts.app')

@section('title', 'Operations — Listora')
@section('robots', 'noindex, nofollow')

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
        {{--
            Tiles are built in DashboardController from what this viewer can
            actually open. The previous version rendered a fixed five for
            anyone who passed isStaff(), so a role without listings.view was
            shown the live listing count and a link to its own 403 — it leaked
            the number, then refused the page.
        --}}
        @if (empty($tiles))
            <div class="empty">
                <h3 style="font-size:24px;margin-bottom:10px">Nothing is assigned to you yet</h3>
                <p>
                    Your account is staff, but holds no module permissions. A super admin can
                    grant them under Roles &amp; Permissions.
                </p>
            </div>
        @else
            <div class="stat-row">
                @foreach ($tiles as $tile)
                    <a href="{{ $tile['url'] }}"
                       class="stat {{ $tile['value'] > 0 && $tile['tone'] ? 'stat-'.$tile['tone'] : '' }}">
                        <span class="stat-n tnum">{{ number_format($tile['value']) }}</span>
                        <span class="stat-l">{{ $tile['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>

@if ($canSeeDrafts)
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
                                <th scope="col">Reference</th>
                                <th scope="col">Owner</th>
                                <th scope="col">What</th>
                                <th scope="col">Plan</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentDrafts as $draft)
                                <tr>
                                    <td>
                                        <code>{{ $draft->reference }}</code>
                                        @if ($draft->source === \App\Models\ListingDraft::SOURCE_SHEET)
                                            <div style="margin-top:6px"><span class="pill pill-off">Information sheet</span></div>
                                        @endif
                                    </td>
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
@endif

@if ($recentActivity->isNotEmpty())
    <section class="pad-sm">
        <div class="wrap">
            <div class="section-head">
                <h2>Recent activity</h2>
                <p class="muted">The last few privileged changes made in the console.</p>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">When</th>
                            <th scope="col">Who</th>
                            <th scope="col">Action</th>
                            <th scope="col">Subject</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentActivity as $entry)
                            <tr>
                                <td class="muted" style="white-space:nowrap">{{ $entry->occurred_at?->diffForHumans() }}</td>
                                <td>{{ $entry->actor?->name ?? 'Deleted user' }}</td>
                                <td><code>{{ $entry->action }}</code></td>
                                <td>
                                    @if ($entry->subject_type)
                                        {{ class_basename($entry->subject_type) }} <span class="muted">#{{ $entry->subject_id }}</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p style="margin-top:22px">
                <a href="{{ route('admin.audit.index') }}" class="btn btn-outline">Open the activity log</a>
            </p>
        </div>
    </section>
@endif

@endsection
