{{--
    Advertising traffic search.

    The investigator's view: full addresses, exact times, every identifier.
    This is the screen the privacy policy means when it says addresses are
    restricted to administrators for security and fraud investigation, so the
    page says as much rather than presenting personal data without comment.
--}}
@extends('layouts.console')

@section('title', 'Advertising traffic')
@section('crumb', 'Advertising traffic')

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">Advertising traffic</h1>
        <p class="c-head__s">Every recorded visit to an advertising or listing page.</p>
    </div>
</div>

<div class="c-note" style="background:var(--c-amber-tint);border-color:#F5DFB6;color:#8A5A00">
    <strong>This screen shows visitor IP addresses.</strong> They are personal data, restricted
    to security and fraud investigation, and are deleted with the record after 24 months.
    Advertisers never see them &mdash; only the approximate location.
</div>

<form method="GET" class="c-card" style="margin-bottom:16px">
    <div class="c-card__b" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1 1 260px">
            <label for="q">Member, ad number, listing number or URL</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] }}"
                   placeholder="202608310253, a name, an email, part of a URL">
        </div>
        <div style="flex:0 1 170px">
            <label for="ip">IP address</label>
            <input type="search" name="ip" id="ip" value="{{ $filters['ip'] }}" placeholder="203.0.113.">
        </div>
        <div style="flex:0 1 170px">
            <label for="region">City, region or country</label>
            <input type="search" name="region" id="region" value="{{ $filters['region'] }}" placeholder="Florida">
        </div>
        <div>
            <label for="from">From</label>
            <input type="date" name="from" id="from" value="{{ $filters['from'] }}">
        </div>
        <div>
            <label for="to">To</label>
            <input type="date" name="to" id="to" value="{{ $filters['to'] }}">
        </div>
        <button type="submit" class="c-btn c-btn--primary">Search</button>
        <a href="{{ route('admin.advertising.index') }}" class="c-btn">Reset</a>
    </div>
</form>

<div class="c-tiles">
    <div class="c-tile">
        <span class="c-tile__l">Matching events</span>
        <span class="c-tile__v">{{ number_format($totals['events']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Distinct visitors</span>
        <span class="c-tile__v">{{ number_format($totals['visitors']) }}</span>
    </div>
</div>

<div class="c-card">
    <div class="c-card__h">
        <h2 class="c-card__t">Events</h2>
        <span class="c-card__link" style="color:var(--c-ink-3);cursor:default">Newest first</span>
    </div>

    @if ($events->isEmpty())
        <div class="c-empty">
            <h3>Nothing matched</h3>
            <p>
                No advertising visits in this period match the search. Widen the dates, or clear
                a filter &mdash; an IP search matches from the start of the address, so a partial
                one sweeps a range.
            </p>
        </div>
    @else
        <div class="c-card__b--flush c-scroll">
            <table class="c-table">
                <thead>
                    <tr>
                        <th scope="col">When</th>
                        <th scope="col">Event</th>
                        <th scope="col">Advertisement</th>
                        <th scope="col">Approximate location</th>
                        <th scope="col">IP address</th>
                        <th scope="col">Source</th>
                        <th scope="col">Device</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr>
                            <td class="c-table__muted" style="white-space:nowrap">
                                {{ $event->occurred_at?->format('d/m/Y H:i') }}
                            </td>
                            <td>{{ $event->event_type?->label() }}</td>
                            <td>
                                @if ($event->member)
                                    <a href="{{ route('admin.advertising.member', $event->member) }}">
                                        <strong>#{{ $event->ad_number }}</strong>
                                    </a>
                                    <div class="c-table__muted">{{ $event->member->name }}</div>
                                @else
                                    <span class="c-table__muted">No advertiser</span>
                                @endif
                                @if ($event->listing)
                                    <div class="c-table__muted">{{ Str::limit($event->listing->title, 34) }}</div>
                                @endif
                            </td>
                            <td>
                                {{ collect([$event->geo_city, $event->geo_region, $event->geo_country])
                                    ->filter()->implode(', ') ?: '—' }}
                            </td>
                            <td><code>{{ $event->ip_address ?? '—' }}</code></td>
                            <td>
                                {{ \App\Services\Advertising\AdTrafficSource::label($event->source_category) }}
                                @if ($event->utm_campaign)
                                    <div class="c-table__muted">{{ $event->utm_campaign }}</div>
                                @endif
                            </td>
                            <td class="c-table__muted">
                                {{ ucfirst($event->device_category) }}
                                @if ($event->browser) &middot; {{ $event->browser }} @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="c-card__b">{{ $events->links() }}</div>
    @endif
</div>

@endsection
