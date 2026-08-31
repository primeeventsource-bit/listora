{{--
    One advertiser's advertising activity, chronologically.

    The log the brief describes: what happened, when, roughly where from, by
    what route, on what device. Rendered as a feed rather than a table because
    it is read as a sequence - "what happened to this advertisement, in order"
    - and a table invites scanning one column instead.
--}}
@extends('layouts.console')

@section('title', 'Activity for '.$member->name)
@section('crumb', 'Advertising activity')

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">{{ $member->name }}</h1>
        <p class="c-head__s">
            Ad number <strong>{{ $member->ad_number }}</strong>
            &middot; {{ $member->email }}
            &middot; <a href="{{ route('ad.member', $member->ad_number) }}" target="_blank" rel="noopener"
                        style="color:var(--c-teal-dark)">public advertising page</a>
        </p>
    </div>
    <div class="c-head__actions">
        <a href="{{ route('admin.advertising.index', ['q' => $member->ad_number]) }}" class="c-btn c-btn--sm">
            Search all traffic
        </a>
    </div>
</div>

<form method="GET" class="c-card" style="margin-bottom:16px">
    <div class="c-card__b" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div>
            <label for="from">From</label>
            <input type="date" name="from" id="from" value="{{ $filters['from'] }}">
        </div>
        <div>
            <label for="to">To</label>
            <input type="date" name="to" id="to" value="{{ $filters['to'] }}">
        </div>
        <button type="submit" class="c-btn c-btn--primary">Apply</button>
    </div>
</form>

<div class="c-card">
    <div class="c-card__h">
        <h2 class="c-card__t">Advertising activity</h2>
        <span class="c-card__link" style="color:var(--c-ink-3);cursor:default">Newest first</span>
    </div>

    @if ($events->isEmpty())
        <div class="c-empty">
            <h3>No activity in this period</h3>
            <p>Nothing was recorded against this advertiser's listings between these dates.</p>
        </div>
    @else
        <ul class="c-feed">
            @foreach ($events as $event)
                <li>
                    <span class="c-feed__dot {{ $event->event_type?->stage() >= 4 ? '' : 'c-feed__dot--grey' }}"></span>
                    <span class="c-feed__txt">
                        <span class="c-feed__who">{{ $event->event_type?->label() }}</span>
                        @if ($event->listing)
                            &mdash; {{ $event->listing->title }}
                            <span class="c-table__muted">#{{ $event->listing_ref }}</span>
                        @endif
                        <div class="c-table__muted" style="margin-top:2px">
                            Approximate location:
                            {{ collect([$event->geo_city, $event->geo_region, $event->geo_country])
                                ->filter()->implode(', ') ?: 'unknown' }}
                            &middot; Source: {{ \App\Services\Advertising\AdTrafficSource::label($event->source_category) }}
                            &middot; Device: {{ ucfirst($event->device_category) }}
                            &middot; IP: <code>{{ $event->ip_address ?? '—' }}</code>
                        </div>
                    </span>
                    <span class="c-feed__when">{{ $event->occurred_at?->format('d/m/Y H:i') }}</span>
                </li>
            @endforeach
        </ul>

        <div class="c-card__b">{{ $events->links() }}</div>
    @endif
</div>

@endsection
