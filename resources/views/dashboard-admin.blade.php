{{--
    Console home.

    Rebuilt on layouts.console. The previous version rendered inside the
    public marketing layout, so the console opened with the site header, the
    site footer, and a 78px nav bar that scrolled away - a logged-in web page
    rather than a tool.

    The information architecture is answer-first: the tiles say what needs a
    person, the queue and the activity feed say what is happening, and the
    listing cards say what the advertising is actually doing. Tiles are still
    built in DashboardController from what this viewer can open, so a role
    without listings.view is not shown the live listing count and a link to
    its own 403.
--}}
@extends('layouts.console')

@section('title', 'Operations')
@section('crumb', 'Dashboard')

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">What needs you today</h1>
        <p class="c-head__s">Advertising operations for {{ config('listora.brand.domain') }}</p>
    </div>
    <div class="c-head__actions">
        @can('reports.view')
            <a href="{{ route('admin.reports.index') }}" class="c-btn c-btn--sm">Performance</a>
        @endcan
        @can('listings.view')
            <a href="{{ route('admin.listings.index') }}" class="c-btn c-btn--primary c-btn--sm">Manage listings</a>
        @endcan
    </div>
</div>

@if (empty($tiles))
    <div class="c-card">
        <div class="c-empty">
            <h3>Nothing is assigned to you yet</h3>
            <p>
                Your account is staff, but holds no module permissions. A super admin can
                grant them under Roles &amp; permissions.
            </p>
        </div>
    </div>
@else
    <div class="c-tiles">
        @foreach ($tiles as $tile)
            <a href="{{ $tile['url'] }}"
               class="c-tile {{ $tile['value'] > 0 && $tile['tone'] ? 'c-tile--'.$tile['tone'] : '' }}">
                <span class="c-tile__l">{{ $tile['label'] }}</span>
                <span class="c-tile__v">{{ number_format($tile['value']) }}</span>
            </a>
        @endforeach
    </div>
@endif

<div class="c-grid c-grid--32">

    @if ($canSeeDrafts)
        <div class="c-card">
            <div class="c-card__h">
                <h2 class="c-card__t">Review queue</h2>
                <a href="{{ route('admin.drafts.index') }}" class="c-card__link">See all</a>
            </div>

            @if ($recentDrafts->isEmpty())
                <div class="c-empty">
                    <h3>The queue is clear</h3>
                    <p>Nothing is waiting on verification. Submissions appear here as owners send them.</p>
                </div>
            @else
                <div class="c-card__b--flush c-scroll">
                    <table class="c-table">
                        <thead>
                            <tr>
                                <th scope="col">Reference</th>
                                <th scope="col">Advertiser</th>
                                <th scope="col">Property</th>
                                <th scope="col">Status</th>
                                <th scope="col">Submitted</th>
                                <th scope="col"><span class="sr-only">Action</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentDrafts as $draft)
                                <tr>
                                    <td><strong>{{ $draft->reference }}</strong></td>
                                    <td>
                                        {{ $draft->owner_name }}
                                        <div class="c-table__muted">{{ $draft->owner_email }}</div>
                                    </td>
                                    <td>{{ $draft->property_name ?: $draft->club_name ?: $draft->city ?: '—' }}</td>
                                    <td><span class="c-pill c-pill--pending">{{ $draft->status?->label() }}</span></td>
                                    <td class="c-table__muted">{{ $draft->created_at?->diffForHumans() }}</td>
                                    <td class="c-table__num">
                                        <a href="{{ route('admin.drafts.show', $draft) }}" class="c-btn c-btn--sm">Review</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    @if ($recentActivity->isNotEmpty())
        <div class="c-card">
            <div class="c-card__h">
                <h2 class="c-card__t">Recent activity</h2>
                <a href="{{ route('admin.audit.index') }}" class="c-card__link">Activity log</a>
            </div>
            <ul class="c-feed">
                @foreach ($recentActivity as $entry)
                    <li>
                        <span class="c-feed__dot {{ str_contains($entry->action, 'delete') || str_contains($entry->action, 'deactivate') ? 'c-feed__dot--amber' : '' }}"></span>
                        <span class="c-feed__txt">
                            <span class="c-feed__who">{{ $entry->actor?->name ?? 'Deleted user' }}</span>
                            {{ str_replace(['.', '_'], ' ', $entry->action) }}
                            @if ($entry->subject_type)
                                <span class="c-table__muted">{{ class_basename($entry->subject_type) }} #{{ $entry->subject_id }}</span>
                            @endif
                        </span>
                        <span class="c-feed__when">{{ $entry->occurred_at?->diffForHumans(short: true) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>

@if ($topListings->isNotEmpty())
    <div style="margin-top:22px">
        <div class="c-head">
            <div>
                <h2 class="c-head__t" style="font-size:17px">Advertising performance</h2>
                <p class="c-head__s">Live listings, busiest first.</p>
            </div>
            <div class="c-head__actions">
                <a href="{{ route('admin.listings.index') }}" class="c-btn c-btn--sm">All listings</a>
            </div>
        </div>

        <div class="c-lcards">
            @foreach ($topListings as $listing)
                @php
                    $photo = is_array($listing->photos) ? ($listing->photos[0] ?? null) : null;
                @endphp
                <a href="{{ route('admin.listings.edit', $listing) }}" class="c-lcard">
                    <div class="c-lcard__img" @if ($photo) style="background-image:url('{{ $photo }}')" @endif>
                        <span class="c-pill c-pill--live c-lcard__pill">Live</span>
                    </div>
                    <div class="c-lcard__b">
                        <h3 class="c-lcard__t">{{ $listing->title }}</h3>
                        <span class="c-lcard__loc">{{ collect([$listing->city, $listing->state])->filter()->implode(', ') ?: $listing->property_name }}</span>
                        <div class="c-lcard__stats">
                            <span class="c-lcard__stat">
                                <span class="c-lcard__sv">{{ number_format($listing->views) }}</span>
                                <span class="c-lcard__sl">Views</span>
                            </span>
                            <span class="c-lcard__stat">
                                <span class="c-lcard__sv">{{ number_format($listing->inquiries_count) }}</span>
                                <span class="c-lcard__sl">Inquiries</span>
                            </span>
                            <span class="c-lcard__stat">
                                <span class="c-lcard__sv">{{ number_format($listing->offers_count) }}</span>
                                <span class="c-lcard__sl">Offers</span>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif

@endsection
