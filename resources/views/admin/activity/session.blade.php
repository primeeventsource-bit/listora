{{--
    One session, in the order it happened.

    Deliberately oldest-first, against the convention everywhere else in the
    console. This screen exists to show a route - homepage, then vacation
    properties, then a listing, then send inquiry - and a route read backwards
    is not a route.

    Rendered as a numbered list rather than a table. The sequence is the
    information here; a table invites the eye to compare columns, which is the
    index screen's job.
--}}
@extends('layouts.console')

@section('title', 'Session timeline — Listora')
@section('crumb', 'Session timeline')

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">Session timeline</h1>
        <p class="c-head__s">
            <code>{{ $sessionId }}</code>
        </p>
    </div>
    <div class="c-head__actions">
        @if ($first->visitor_id)
            <a href="{{ route('admin.activity.visitor', $first->visitor_id) }}" class="c-btn c-btn--sm">
                Visitor profile
            </a>
        @endif
        <a href="{{ route('admin.activity.index') }}" class="c-btn c-btn--sm">Back to log</a>
    </div>
</div>

<div class="c-tiles">
    <div class="c-tile">
        <span class="c-tile__l">Events</span>
        <span class="c-tile__v">{{ number_format($events->count()) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Duration</span>
        <span class="c-tile__v" style="font-size:20px">{{ $duration }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Started</span>
        <span class="c-tile__v" style="font-size:20px">{{ $first->occurred_at?->format('j M Y H:i') }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Account</span>
        <span class="c-tile__v" style="font-size:20px">{{ $account?->name ?? 'Anonymous' }}</span>
    </div>
</div>

<div class="c-card" style="margin-bottom:16px">
    <div class="c-card__h"><h2 class="c-card__t">Session details</h2></div>
    <div class="c-card__b">
        <dl class="detail-list">
            <dt>IP address</dt><dd>{{ $first->ip_address ?? '—' }}</dd>
            <dt>Approximate location</dt>
            <dd>
                {{ collect([$first->geo_city, $first->geo_region, $first->geo_country])->filter()->implode(', ') ?: 'Unknown' }}
                <span class="c-table__muted">— estimated from the connection, not a precise position</span>
            </dd>
            <dt>Device</dt>
            <dd>
                {{ ucfirst($first->device_category) }}
                @if ($first->browser) &middot; {{ $first->browser }} @endif
                @if ($first->os) &middot; {{ $first->os }} @endif
            </dd>
            <dt>Entry page</dt><dd>/{{ $first->path ?: '' }}</dd>
            <dt>Referral source</dt>
            <dd>
                {{ $first->referrer_host ?: 'Direct' }}
                @if ($first->utm_campaign) &middot; campaign {{ $first->utm_campaign }} @endif
                @if ($first->source_category) <span class="c-table__muted">({{ $first->source_category }})</span> @endif
            </dd>
            <dt>Visitor ID</dt><dd><code>{{ $first->visitor_id ?? '—' }}</code></dd>
            @if ($account)
                <dt>Identified as</dt>
                <dd>
                    <a href="{{ route('admin.users.edit', $account) }}">{{ $account->name }}</a>
                    &middot; {{ $account->email }}
                    @if ($account->ad_number) &middot; member {{ $account->ad_number }} @endif
                </dd>
            @endif
        </dl>
    </div>
</div>

<div class="c-card">
    <div class="c-card__h">
        <h2 class="c-card__t">What happened, in order</h2>
        <span class="c-card__link" style="color:var(--c-ink-3);cursor:default">Oldest first</span>
    </div>
    <div class="c-card__b--flush c-scroll">
        <table class="c-table">
            <thead>
                <tr>
                    <th scope="col" style="width:52px">#</th>
                    <th scope="col" style="width:110px">Time</th>
                    <th scope="col">Activity</th>
                    <th scope="col">Page or listing</th>
                    <th scope="col">Signed in</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $i => $event)
                    <tr>
                        <td class="c-table__muted">{{ $i + 1 }}</td>
                        <td class="c-table__muted" style="white-space:nowrap">
                            {{ $event->occurred_at?->format('H:i:s') }}
                        </td>
                        <td><strong>{{ $event->event_type?->label() ?? $event->getRawOriginal('event_type') }}</strong></td>
                        <td>
                            @if ($event->listing)
                                <a href="{{ route('admin.listings.edit', $event->listing) }}">{{ Str::limit($event->listing->title, 44) }}</a>
                                <div class="c-table__muted">{{ $event->listing_ref }}</div>
                            @else
                                <span class="c-table__muted">/{{ $event->path }}</span>
                            @endif
                        </td>
                        <td class="c-table__muted">{{ $event->actor?->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
