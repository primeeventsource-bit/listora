{{--
    The activity log.

    A filter bar, four counts, a map, and the rows. The filters are a plain
    GET form on purpose: an administrator working a dispute needs to be able
    to paste the resulting URL into a ticket, and a filter that only exists in
    JavaScript state cannot be sent to anyone.

    Every row offers the two things worth clicking - the session it belongs
    to, and the visitor who had it - because a single row almost never answers
    the question that brought somebody here.
--}}
@extends('layouts.console')

@section('title', 'Activity log — Listora')
@section('crumb', 'Activity log')

@push('head')
    @if ($mapboxToken)
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css" rel="stylesheet">
    @endif
@endpush

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">Activity log</h1>
        <p class="c-head__s">
            Every recorded visit, anonymous and signed in.
            Addresses and full history are restricted to this screen.
        </p>
    </div>
    @can('activity.export')
        <div class="c-head__actions">
            <a href="{{ route('admin.activity.export', request()->query()) }}" class="c-btn c-btn--sm">
                Export CSV
            </a>
        </div>
    @endcan
</div>

<form method="GET" class="c-card" style="margin-bottom:16px">
    <div class="c-card__b" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:12px;align-items:end">
        <div style="grid-column:span 2">
            <label for="q">Search</label>
            <input type="search" name="q" id="q" value="{{ $filters['q'] }}"
                   placeholder="Address, page, ad number, campaign, city">
        </div>
        <div>
            <label for="type">Activity</label>
            <select name="type" id="type">
                <option value="">All activity</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="device">Device</label>
            <select name="device" id="device">
                <option value="">Any device</option>
                @foreach (['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet', 'unknown' => 'Unknown'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['device'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="ip">IP address</label>
            <input type="text" name="ip" id="ip" value="{{ $filters['ip'] }}" placeholder="Exact match">
        </div>
        <div>
            <label for="city">City</label>
            <input type="text" name="city" id="city" value="{{ $filters['city'] }}">
        </div>
        <div>
            <label for="country">Country</label>
            <input type="text" name="country" id="country" value="{{ $filters['country'] }}"
                   maxlength="2" placeholder="US">
        </div>
        <div>
            <label for="user">User ID</label>
            <input type="text" name="user" id="user" value="{{ $filters['user'] }}">
        </div>
        <div>
            <label for="listing">Listing / ad</label>
            <input type="text" name="listing" id="listing" value="{{ $filters['listing'] }}"
                   placeholder="ID or ad number">
        </div>
        <div>
            <label for="session">Session</label>
            <input type="text" name="session" id="session" value="{{ $filters['session'] }}">
        </div>
        <div>
            <label for="from">From</label>
            <input type="date" name="from" id="from" value="{{ $filters['from'] }}">
        </div>
        <div>
            <label for="to">To</label>
            <input type="date" name="to" id="to" value="{{ $filters['to'] }}">
        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="c-btn c-btn--primary">Apply</button>
            <a href="{{ route('admin.activity.index') }}" class="c-btn">Clear</a>
        </div>
    </div>
</form>

<div class="c-tiles">
    <div class="c-tile">
        <span class="c-tile__l">Events</span>
        <span class="c-tile__v">{{ number_format($totals['events']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Sessions</span>
        <span class="c-tile__v">{{ number_format($totals['sessions']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Visitors</span>
        <span class="c-tile__v">{{ number_format($totals['visitors']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Identified accounts</span>
        <span class="c-tile__v">{{ number_format($totals['accounts']) }}</span>
    </div>
</div>

@if (! empty($points))
    <div class="c-card" style="margin-bottom:16px">
        <div class="c-card__h">
            <h2 class="c-card__t">Where this traffic came from</h2>
            <span class="c-card__link" style="color:var(--c-ink-3);cursor:default">Approximate</span>
        </div>
        @if ($mapboxToken)
            <div id="activity-map" style="height:340px;background:var(--c-surface-2)"></div>
        @else
            <div class="c-card__b">
                <div class="c-note">
                    <strong>No basemap configured.</strong> Locations are still recorded and searchable —
                    only the map behind them is missing.
                </div>
            </div>
        @endif
    </div>
@endif

<div class="c-card">
    <div class="c-card__h">
        <h2 class="c-card__t">Recorded activity</h2>
        <span class="c-card__link" style="color:var(--c-ink-3);cursor:default">Newest first</span>
    </div>

    @if ($events->isEmpty())
        <div class="c-empty">
            <h3>Nothing matches these filters</h3>
            <p>Widen the date range, or clear the filters and start again.</p>
        </div>
    @else
        <div class="c-card__b--flush c-scroll">
            <table class="c-table">
                <thead>
                    <tr>
                        <th scope="col">When</th>
                        <th scope="col">Activity</th>
                        <th scope="col">Who</th>
                        <th scope="col">Where from</th>
                        <th scope="col">Page / listing</th>
                        <th scope="col">Device</th>
                        <th scope="col"></th>
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
                                @if ($event->actor)
                                    <a href="{{ route('admin.users.edit', $event->actor) }}">{{ $event->actor->name }}</a>
                                    <div class="c-table__muted">{{ $event->actor->email }}</div>
                                @else
                                    <span class="c-table__muted">Anonymous</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $event->ip_address ?? '—' }}</div>
                                <div class="c-table__muted">
                                    {{ collect([$event->geo_city, $event->geo_region, $event->geo_country])->filter()->implode(', ') ?: 'Location unknown' }}
                                </div>
                            </td>
                            <td>
                                @if ($event->listing)
                                    <a href="{{ route('admin.listings.edit', $event->listing) }}">{{ Str::limit($event->listing->title, 38) }}</a>
                                    <div class="c-table__muted">{{ $event->listing_ref }}</div>
                                @else
                                    <span class="c-table__muted">/{{ $event->path }}</span>
                                @endif
                            </td>
                            <td class="c-table__muted">
                                {{ ucfirst($event->device_category) }}
                                @if ($event->browser) &middot; {{ $event->browser }} @endif
                            </td>
                            <td style="white-space:nowrap">
                                @if ($event->session_id)
                                    <a href="{{ route('admin.activity.session', $event->session_id) }}"
                                       class="c-btn c-btn--sm">Session</a>
                                @endif
                                @if ($event->visitor_id)
                                    <a href="{{ route('admin.activity.visitor', $event->visitor_id) }}"
                                       class="c-btn c-btn--sm">Visitor</a>
                                @endif
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

@push('scripts')
@if ($mapboxToken && ! empty($points))
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js"></script>
    <script>
    (function () {
        var el = document.getElementById('activity-map');
        if (!el || typeof mapboxgl === 'undefined') return;

        mapboxgl.accessToken = @json($mapboxToken);

        var points = @json($points);
        var max = points.reduce(function (m, p) { return Math.max(m, p.events); }, 1);

        var map = new mapboxgl.Map({
            container: 'activity-map',
            style: @json($mapboxStyle ?: 'mapbox://styles/mapbox/light-v11'),
            center: [points[0].lng, points[0].lat],
            zoom: points.length === 1 ? 5 : 1.4,
        });

        map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

        map.on('load', function () {
            map.addSource('activity', {
                type: 'geojson',
                data: {
                    type: 'FeatureCollection',
                    features: points.map(function (p) {
                        return {
                            type: 'Feature',
                            geometry: { type: 'Point', coordinates: [p.lng, p.lat] },
                            properties: { events: p.events, label: p.label },
                        };
                    }),
                },
            });

            map.addLayer({
                id: 'activity-points',
                type: 'circle',
                source: 'activity',
                paint: {
                    // Area, not radius, scales with the count - a radius that
                    // scales linearly makes a marker with twice the events
                    // look four times the size.
                    'circle-radius': ['interpolate', ['linear'], ['get', 'events'],
                        1, 4, Math.max(max, 2), 24],
                    'circle-color': '#009D9A',
                    'circle-opacity': 0.6,
                    'circle-stroke-width': 1.4,
                    'circle-stroke-color': '#00807E',
                },
            });

            var popup = new mapboxgl.Popup({ closeButton: false, closeOnClick: false });

            map.on('mouseenter', 'activity-points', function (e) {
                map.getCanvas().style.cursor = 'pointer';
                var f = e.features[0];
                popup.setLngLat(f.geometry.coordinates)
                    .setText(f.properties.label + ' — ' + f.properties.events + ' events (approximate)')
                    .addTo(map);
            });

            map.on('mouseleave', 'activity-points', function () {
                map.getCanvas().style.cursor = '';
                popup.remove();
            });
        });
    })();
    </script>
@endif
@endpush
