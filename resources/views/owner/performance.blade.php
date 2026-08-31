{{--
    Advertising performance for one advertiser.

    Opens with the two things that identify their advertising - the Ad Number
    and the public URL - because those are what a member quotes when they ask
    a question about it, and they should not have to go looking.

    Everything geographic is labelled approximate, in the interface and not
    only in the privacy policy. It comes from an IP lookup, which places a
    visitor near their network rather than near themselves, and an
    unqualified "Orlando" invites an advertiser to believe something the data
    does not support.
--}}
@extends('layouts.member')

@section('title', 'Advertising performance')
@section('crumb', 'Performance')

@push('head')
    @if ($mapboxToken)
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css" rel="stylesheet">
    @endif
@endpush

@section('content')

<div class="c-head">
    <div>
        <h1 class="c-head__t">Advertising performance</h1>
        <p class="c-head__s">
            Ad number <strong>{{ $member->ad_number }}</strong>
            &middot;
            <a href="{{ route('ad.member', $member->ad_number) }}" target="_blank" rel="noopener"
               style="color:var(--c-teal-dark)">{{ config('listora.brand.domain') }}/ad/{{ $member->ad_number }}</a>
        </p>
    </div>
</div>

{{-- Filters. A plain GET form: these are shareable, bookmarkable views of
     someone's own numbers, and a filter that only lives in JavaScript state
     cannot be sent to anyone. --}}
<form method="GET" class="c-card" style="margin-bottom:16px">
    <div class="c-card__b" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div>
            <label for="range">Period</label>
            <select name="range" id="range" onchange="this.form.submit()" style="min-width:170px">
                <option value="today" @selected($rangeKey === 'today')>Today</option>
                <option value="7d" @selected($rangeKey === '7d')>Last 7 days</option>
                <option value="30d" @selected($rangeKey === '30d')>Last 30 days</option>
                <option value="90d" @selected($rangeKey === '90d')>Last 90 days</option>
                <option value="custom" @selected($rangeKey === 'custom')>Custom range</option>
            </select>
        </div>

        @if ($rangeKey === 'custom')
            <div>
                <label for="from">From</label>
                <input type="date" name="from" id="from" value="{{ $from->toDateString() }}">
            </div>
            <div>
                <label for="to">To</label>
                <input type="date" name="to" id="to" value="{{ $to->toDateString() }}">
            </div>
        @endif

        <div>
            <label for="listing">Listing</label>
            <select name="listing" id="listing" onchange="this.form.submit()" style="min-width:220px">
                <option value="">All listings</option>
                @foreach ($listings as $listing)
                    <option value="{{ $listing->id }}" @selected($selectedListing?->id === $listing->id)>
                        {{ Str::limit($listing->title, 44) }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="c-btn c-btn--primary">Apply</button>
    </div>
</form>

<div class="c-tiles">
    <div class="c-tile">
        <span class="c-tile__l">Advertisement views</span>
        <span class="c-tile__v">{{ number_format($totals['views']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Unique visitors</span>
        <span class="c-tile__v">{{ number_format($totals['visitors']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Inquiries</span>
        <span class="c-tile__v">{{ number_format($totals['inquiries']) }}</span>
    </div>
    <div class="c-tile">
        <span class="c-tile__l">Offers</span>
        <span class="c-tile__v">{{ number_format($totals['offers']) }}</span>
    </div>
</div>

<div class="c-card" style="margin-bottom:16px">
    <div class="c-card__h">
        <h2 class="c-card__t">Where your traffic came from</h2>
        <span class="c-card__link" style="color:var(--c-ink-3);cursor:default">Approximate</span>
    </div>

    @if (empty($points))
        <div class="c-empty">
            <h3>No located traffic in this period</h3>
            <p>
                Views are only placed on the map once a visitor's approximate location can be
                worked out from their connection. Widen the period, or check back after your
                advertising has been running a while.
            </p>
        </div>
    @else
        @unless ($mapboxToken)
            <div class="c-note" style="margin:15px 15px 0">
                <strong>No basemap configured.</strong> The places below are correct and complete —
                only the map behind them is missing.
            </div>
        @endunless

        @if ($mapboxToken)
            <div id="ad-map" style="height:380px;background:var(--c-surface-2)"></div>
        @endif

        <div class="c-card__b">
            <p style="margin:0 0 10px;font-size:12.5px;color:var(--c-ink-3)">
                Locations are estimated from each visitor's IP address. They indicate the area a
                visitor connected through, which is often not where they actually are, and never
                a precise position.
            </p>
            <div class="c-scroll">
                <table class="c-table">
                    <thead>
                        <tr>
                            <th scope="col">Approximate location</th>
                            <th scope="col" class="c-table__num">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($places as $place => $count)
                            <tr>
                                <td>{{ $place }}</td>
                                <td class="c-table__num">{{ number_format($count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<div class="c-grid c-grid--2" style="margin-bottom:16px">
    <div class="c-card">
        <div class="c-card__h"><h2 class="c-card__t">Traffic source</h2></div>
        @if (empty($sources))
            <div class="c-empty"><p>No views in this period.</p></div>
        @else
            <div class="c-scroll">
                <table class="c-table">
                    <tbody>
                        @foreach ($sources as $label => $count)
                            <tr>
                                <td>{{ $label }}</td>
                                <td class="c-table__num">{{ number_format($count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="c-card">
        <div class="c-card__h"><h2 class="c-card__t">Device</h2></div>
        @if (empty($devices))
            <div class="c-empty"><p>No views in this period.</p></div>
        @else
            <div class="c-scroll">
                <table class="c-table">
                    <tbody>
                        @foreach ($devices as $device => $count)
                            <tr>
                                <td>{{ ucfirst($device) }}</td>
                                <td class="c-table__num">{{ number_format($count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@if (! empty($perListing))
    <div class="c-card">
        <div class="c-card__h"><h2 class="c-card__t">By listing</h2></div>
        <div class="c-scroll">
            <table class="c-table">
                <thead>
                    <tr>
                        <th scope="col">Listing</th>
                        <th scope="col">Ad number</th>
                        <th scope="col" class="c-table__num">Views</th>
                        <th scope="col" class="c-table__num">Unique visitors</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perListing as $row)
                        <tr>
                            <td>
                                <a href="{{ route('owner.listings.edit', $row['listing']) }}">
                                    <strong>{{ $row['listing']->title }}</strong>
                                </a>
                            </td>
                            <td class="c-table__muted">{{ $row['listing']->ad_number ?? '—' }}</td>
                            <td class="c-table__num">{{ number_format($row['views']) }}</td>
                            <td class="c-table__num">{{ number_format($row['visitors']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection

@push('scripts')
@if ($mapboxToken && ! empty($points))
    <script src="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js"></script>
    <script>
    (function () {
        var el = document.getElementById('ad-map');
        if (!el || typeof mapboxgl === 'undefined') return;

        mapboxgl.accessToken = @json($mapboxToken);

        var points = @json($points);
        var max = points.reduce(function (m, p) { return Math.max(m, p.events); }, 1);

        var map = new mapboxgl.Map({
            container: 'ad-map',
            style: @json($mapboxStyle ?: 'mapbox://styles/mapbox/light-v11'),
            center: [points[0].lng, points[0].lat],
            zoom: points.length === 1 ? 5 : 2,
        });

        map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

        map.on('load', function () {
            map.addSource('traffic', {
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
                id: 'traffic-points',
                type: 'circle',
                source: 'traffic',
                paint: {
                    // Area, not radius, scales with the count - a radius that
                    // scales linearly makes a marker with twice the views look
                    // four times the size.
                    'circle-radius': ['interpolate', ['linear'], ['get', 'events'],
                        1, 5, Math.max(max, 2), 22],
                    'circle-color': '#009D9A',
                    'circle-opacity': 0.62,
                    'circle-stroke-width': 1.5,
                    'circle-stroke-color': '#00807E',
                },
            });

            var popup = new mapboxgl.Popup({ closeButton: false, closeOnClick: false });

            map.on('mouseenter', 'traffic-points', function (e) {
                map.getCanvas().style.cursor = 'pointer';
                var f = e.features[0];
                popup.setLngLat(f.geometry.coordinates)
                    .setText(f.properties.label + ' — ' + f.properties.events + ' views (approximate)')
                    .addTo(map);
            });

            map.on('mouseleave', 'traffic-points', function () {
                map.getCanvas().style.cursor = '';
                popup.remove();
            });
        });
    })();
    </script>
@endif
@endpush
