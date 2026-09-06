{{--
    One visitor, across every session they have had.

    The visitor id is a first-party opaque identifier, not an account. Someone
    anonymous gets one on arrival and keeps it; if they sign in later, the
    account shows here beside the earlier activity - which is usually the
    point, because a dispute is normally about what somebody did before they
    had an account.

    Capped at the most recent 500 events. A profile that tries to render four
    years of a crawler's traffic is a screen nobody can open.
--}}
@extends('layouts.console')

@section('title', 'Visitor profile — Listora')
@section('crumb', 'Visitor profile')

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">Visitor profile</h1>
        <p class="c-head__s"><code>{{ $visitorId }}</code></p>
    </div>
    <div class="c-head__actions">
        <a href="{{ route('admin.activity.index', ['visitor' => $visitorId]) }}" class="c-btn c-btn--sm">
            Filter the log
        </a>
        <a href="{{ route('admin.activity.index') }}" class="c-btn c-btn--sm">Back to log</a>
    </div>
</div>

<div class="c-tiles">
    <div class="c-tile">
        <span class="c-tile__l">Sessions</span>
        <span class="c-tile__v">{{ number_format($summary['sessions']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Page views</span>
        <span class="c-tile__v">{{ number_format($summary['page_views']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Recorded events</span>
        <span class="c-tile__v">{{ number_format($summary['events']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Account</span>
        <span class="c-tile__v" style="font-size:20px">{{ $account?->name ?? 'Anonymous' }}</span>
    </div>
</div>

<div class="c-card" style="margin-bottom:16px">
    <div class="c-card__h"><h2 class="c-card__t">Identity and origin</h2></div>
    <div class="c-card__b">
        <dl class="detail-list">
            <dt>First seen</dt><dd>{{ $summary['first_seen']?->format('j M Y H:i') ?? '—' }}</dd>
            <dt>Most recent</dt><dd>{{ $summary['last_seen']?->format('j M Y H:i') ?? '—' }}</dd>
            <dt>IP addresses</dt>
            <dd>{{ implode(', ', $summary['addresses']) ?: '—' }}</dd>
            <dt>Approximate locations</dt>
            <dd>
                {{ implode(' · ', $summary['places']) ?: 'Unknown' }}
                <span class="c-table__muted">— estimated from the connection, never a precise position</span>
            </dd>
            <dt>Devices</dt>
            <dd>{{ collect($summary['devices'])->map(fn ($d) => ucfirst($d))->implode(', ') ?: '—' }}</dd>
            @if ($account)
                <dt>Identified as</dt>
                <dd>
                    <a href="{{ route('admin.users.edit', $account) }}">{{ $account->name }}</a>
                    &middot; {{ $account->email }}
                    @if ($account->ad_number) &middot; member {{ $account->ad_number }} @endif
                </dd>
            @else
                <dt>Identified as</dt>
                <dd class="c-table__muted">
                    Never signed in. Activity is associated with this visitor identifier only.
                </dd>
            @endif
        </dl>
    </div>
</div>

<div class="c-grid c-grid--2" style="margin-bottom:16px">
    <div class="c-card">
        <div class="c-card__h"><h2 class="c-card__t">Sessions</h2></div>
        <div class="c-card__b--flush c-scroll">
            <table class="c-table">
                <thead>
                    <tr>
                        <th scope="col">Started</th>
                        <th scope="col">Entry page</th>
                        <th scope="col" class="c-table__num">Events</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sessions as $id => $session)
                        <tr>
                            <td class="c-table__muted" style="white-space:nowrap">
                                {{ $session['started']?->format('j M Y H:i') }}
                            </td>
                            <td class="c-table__muted">/{{ $session['entry'] }}</td>
                            <td class="c-table__num">{{ number_format($session['events']) }}</td>
                            <td>
                                <a href="{{ route('admin.activity.session', $id) }}" class="c-btn c-btn--sm">Timeline</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="c-card">
        <div class="c-card__h"><h2 class="c-card__t">Listings viewed</h2></div>
        @if ($listings->isEmpty())
            <div class="c-empty"><p>No listing pages in the recorded history.</p></div>
        @else
            <div class="c-card__b--flush c-scroll">
                <table class="c-table">
                    <tbody>
                        @foreach ($listings as $row)
                            <tr>
                                <td>
                                    @if ($row['listing'])
                                        <a href="{{ route('admin.listings.edit', $row['listing']) }}">
                                            {{ Str::limit($row['listing']->title, 40) }}
                                        </a>
                                        <div class="c-table__muted">{{ $row['listing']->ad_number }}</div>
                                    @else
                                        <span class="c-table__muted">Listing removed</span>
                                    @endif
                                </td>
                                <td class="c-table__num">{{ number_format($row['views']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@if ($inquiries->isNotEmpty() || $offers->isNotEmpty())
    <div class="c-grid c-grid--2" style="margin-bottom:16px">
        <div class="c-card">
            <div class="c-card__h"><h2 class="c-card__t">Inquiries sent</h2></div>
            <div class="c-card__b--flush c-scroll">
                <table class="c-table">
                    <tbody>
                        @foreach ($inquiries as $inquiry)
                            <tr>
                                <td>{{ $inquiry->created_at?->format('j M Y H:i') }}</td>
                                <td class="c-table__muted">Listing #{{ $inquiry->listing_id }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="c-card">
            <div class="c-card__h"><h2 class="c-card__t">Offers submitted</h2></div>
            <div class="c-card__b--flush c-scroll">
                <table class="c-table">
                    <tbody>
                        @foreach ($offers as $offer)
                            <tr>
                                <td>{{ $offer->created_at?->format('j M Y H:i') }}</td>
                                <td class="c-table__muted">Listing #{{ $offer->listing_id }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<div class="c-card">
    <div class="c-card__h">
        <h2 class="c-card__t">All recorded activity</h2>
        <span class="c-card__link" style="color:var(--c-ink-3);cursor:default">
            Newest first &middot; most recent 500
        </span>
    </div>
    <div class="c-card__b--flush c-scroll">
        <table class="c-table">
            <thead>
                <tr>
                    <th scope="col">When</th>
                    <th scope="col">Activity</th>
                    <th scope="col">Page or listing</th>
                    <th scope="col">From</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $event)
                    <tr>
                        <td class="c-table__muted" style="white-space:nowrap">
                            {{ $event->occurred_at?->format('j M Y H:i:s') }}
                        </td>
                        <td>{{ $event->event_type?->label() ?? $event->getRawOriginal('event_type') }}</td>
                        <td>
                            @if ($event->listing)
                                <a href="{{ route('admin.listings.edit', $event->listing) }}">{{ Str::limit($event->listing->title, 38) }}</a>
                            @else
                                <span class="c-table__muted">/{{ $event->path }}</span>
                            @endif
                        </td>
                        <td class="c-table__muted">{{ $event->ip_address ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
