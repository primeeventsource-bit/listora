@extends('layouts.app')

@section('title', 'Reports — Listora')
@section('robots', 'noindex, nofollow')

@push('head')
    @if ($mapboxToken)
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css" rel="stylesheet">
    @endif
@endpush

@section('content')

@include('partials.account-nav')

<div class="page-head">
    <div class="wrap">
        <span class="eyebrow">Operations</span>
        <h1>Reports</h1>
        <p>Where traffic comes from, what it does, and which campaigns brought it.</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">

        <form method="GET" action="{{ route('admin.reports.index') }}" class="filter-form">
            <select name="days" onchange="this.form.submit()">
                @foreach ($windows as $value => $label)
                    <option value="{{ $value }}" @selected($days === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-navy btn-sm">Apply</button></noscript>

            @can('reports.export')
                <a href="{{ route('admin.reports.export', ['days' => $days]) }}" class="btn btn-outline btn-sm">
                    Export countries (CSV)
                </a>
            @endcan
        </form>

        @if ($truncated)
            <div class="notice amber">
                This window holds more events than the page aggregates in one pass, so the
                figures below cover the most recent slice rather than the whole period.
                Narrow the window for an exact count.
            </div>
        @endif

        <div class="stat-row" style="margin-bottom:36px">
            <div class="stat">
                <span class="stat-n tnum">{{ number_format($totals['events']) }}</span>
                <span class="stat-l">Events</span>
            </div>
            <div class="stat">
                <span class="stat-n tnum">{{ number_format($totals['visitors']) }}</span>
                <span class="stat-l">Unique visitors</span>
            </div>
            <div class="stat">
                <span class="stat-n tnum">{{ number_format($totals['countries']) }}</span>
                <span class="stat-l">Countries</span>
            </div>
            <div class="stat">
                <span class="stat-n tnum">{{ number_format($totals['attributed']) }}</span>
                <span class="stat-l">Attributed arrivals</span>
            </div>
        </div>
    </div>
</section>

{{-- ------------------------------- the map ------------------------------- --}}
<section class="pad-sm">
    <div class="wrap">
        <div class="section-head">
            <h2>Where visitors are</h2>
            <p class="muted">
                One pin per location, sized by event volume. Positions come from
                <code>metadata.geo</code>, written by GeoIpService when the event was recorded —
                so this is where the IP resolved, not where anyone said they were.
            </p>
        </div>

        @if (empty($points))
            <div class="empty">
                <h3 style="font-size:22px;margin-bottom:10px">No located traffic in this window</h3>
                <p>
                    Events are recorded without geo when no GeoIP provider is configured.
                    Widen the window, or seed demo traffic on a non-production environment with
                    <code>php artisan db:seed --class=DemoTrafficSeeder</code>.
                </p>
            </div>
        @elseif ($mapboxToken)
            <div id="visitorMap" style="height:520px;border-radius:var(--r-lg);overflow:hidden;border:1px solid var(--line)"></div>
        @else
            {{--
                No token, no third-party call. The same pins, plotted on an
                equirectangular grid — every number on this page is still
                correct, only the basemap is missing.
            --}}
            <div class="notice">
                <strong>No Mapbox token configured.</strong> Showing a plotted grid instead.
                Set <code>MAPBOX_ACCESS_TOKEN</code> to render the basemap.
            </div>

            @php
                $maxEvents = max(array_column($points, 'events'));
            @endphp

            <div class="table-wrap" style="padding:0">
                <svg viewBox="0 0 720 360" style="width:100%;height:auto;display:block;background:var(--cream)"
                     role="img" aria-label="Visitor locations plotted by latitude and longitude">
                    @foreach ([-120, -60, 0, 60, 120] as $lng)
                        <line x1="{{ ($lng + 180) / 360 * 720 }}" y1="0" x2="{{ ($lng + 180) / 360 * 720 }}" y2="360"
                              stroke="var(--line)" stroke-width="1"/>
                    @endforeach
                    @foreach ([-60, -30, 0, 30, 60] as $lat)
                        <line x1="0" y1="{{ (90 - $lat) / 180 * 360 }}" x2="720" y2="{{ (90 - $lat) / 180 * 360 }}"
                              stroke="var(--line)" stroke-width="{{ $lat === 0 ? 1.5 : 1 }}"/>
                    @endforeach

                    @foreach ($points as $point)
                        <circle cx="{{ round(($point['lng'] + 180) / 360 * 720, 2) }}"
                                cy="{{ round((90 - $point['lat']) / 180 * 360, 2) }}"
                                r="{{ round(3 + ($point['events'] / $maxEvents) * 12, 2) }}"
                                fill="var(--teal)" fill-opacity="0.45" stroke="var(--teal-dark)" stroke-width="1">
                            <title>{{ $point['label'] }} — {{ number_format($point['events']) }} events</title>
                        </circle>
                    @endforeach
                </svg>
            </div>
        @endif

        @if ($anonymised['vpn'] || $anonymised['tor'] || $anonymised['datacenter'])
            <p class="muted" style="margin-top:16px">
                <strong>{{ number_format($anonymised['vpn']) }}</strong> events came through a VPN,
                <strong>{{ number_format($anonymised['tor']) }}</strong> through Tor, and
                <strong>{{ number_format($anonymised['datacenter']) }}</strong> from a datacenter range.
                They are counted above rather than dropped — a map that silently discards them
                reports where people appear to be with no note that some of them are not there.
            </p>
        @endif
    </div>
</section>

{{-- ---------------------------- traffic + clicks --------------------------- --}}
<section class="pad-sm">
    <div class="wrap">
        <div class="section-head">
            <h2>Events per day</h2>
        </div>

        @php
            $peak = max(1, max(array_column($daily, 'events')));
        @endphp

        <div class="table-wrap" style="padding:20px">
            <svg viewBox="0 0 {{ max(300, count($daily) * 12) }} 140" style="width:100%;height:180px;display:block"
                 role="img" aria-label="Daily event volume over the selected window">
                @foreach ($daily as $i => $point)
                    <rect x="{{ $i * 12 + 2 }}"
                          y="{{ round(140 - ($point['events'] / $peak * 128), 2) }}"
                          width="8"
                          height="{{ max(1, round($point['events'] / $peak * 128, 2)) }}"
                          fill="var(--teal)" rx="2">
                        <title>{{ $point['label'] }} — {{ number_format($point['events']) }} events</title>
                    </rect>
                @endforeach
            </svg>
            <p class="muted" style="margin:12px 0 0;font-size:13px">
                {{ $daily[0]['label'] ?? '' }} &rarr; {{ $daily[count($daily) - 1]['label'] ?? '' }} · peak {{ number_format($peak) }}/day
            </p>
        </div>
    </div>
</section>

<section class="pad-sm">
    <div class="wrap">
        <div class="grid g2" style="gap:32px">

            <div>
                <div class="section-head"><h2>What was clicked</h2></div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th scope="col">Event</th><th scope="col">Count</th></tr></thead>
                        <tbody>
                            @forelse ($byType as $type => $count)
                                <tr>
                                    <td><code>{{ $type }}</code></td>
                                    <td class="tnum">{{ number_format($count) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="muted">Nothing recorded in this window.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="section-head" style="margin-top:32px"><h2>Surface</h2></div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th scope="col">Surface</th><th scope="col">Count</th></tr></thead>
                        <tbody>
                            @foreach ($bySurface as $surface => $count)
                                <tr><td>{{ $surface }}</td><td class="tnum">{{ number_format($count) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="section-head"><h2>Top countries</h2></div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr><th scope="col">Country</th><th scope="col">Events</th><th scope="col">Visitors</th><th scope="col">Share</th></tr>
                        </thead>
                        <tbody>
                            @forelse (array_slice($countries, 0, 12) as $row)
                                <tr>
                                    <td>{{ $row['country'] }}</td>
                                    <td class="tnum">{{ number_format($row['events']) }}</td>
                                    <td class="tnum">{{ number_format($row['visitors']) }}</td>
                                    <td class="tnum">{{ $row['share'] }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">No located traffic in this window.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="section-head" style="margin-top:32px"><h2>Top cities</h2></div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th scope="col">City</th><th scope="col">Country</th><th scope="col">Events</th></tr></thead>
                        <tbody>
                            @forelse ($cities as $row)
                                <tr>
                                    <td>{{ $row['city'] }}</td>
                                    <td>{{ $row['country'] }}</td>
                                    <td class="tnum">{{ number_format($row['events']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">No city-level data. Most GeoIP tiers resolve country only.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ------------------------------- campaigns ------------------------------ --}}
<section class="pad-sm">
    <div class="wrap">
        <div class="section-head">
            <h2>Where paid arrivals came from</h2>
            <p class="muted">
                First touch, from <code>ppc_visitors</code> — a returning visitor keeps the campaign
                that first brought them rather than being reassigned to their latest click.
            </p>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th scope="col">Source</th><th scope="col">Medium</th><th scope="col">Campaign</th><th scope="col">Visitors</th></tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $row)
                        <tr>
                            <td>{{ $row['source'] }}</td>
                            <td>{{ $row['medium'] }}</td>
                            <td>{{ $row['campaign'] }}</td>
                            <td class="tnum">{{ number_format($row['visitors']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">
                                Nothing attributed in this window. Arrivals are recorded only when a
                                visitor lands carrying <code>gclid</code> or <code>utm_*</code>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

@endsection

@if ($mapboxToken && ! empty($points))
    @push('scripts')
        <script src="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.js"></script>
        <script>
            (function () {
                var el = document.getElementById('visitorMap');
                if (!el || typeof mapboxgl === 'undefined') return;

                mapboxgl.accessToken = @json($mapboxToken);

                var points = @json($points);
                var max = points.reduce(function (m, p) { return Math.max(m, p.events); }, 1);

                var map = new mapboxgl.Map({
                    container: 'visitorMap',
                    style: @json($mapboxStyle),
                    center: [-40, 30],
                    zoom: 1.4,
                    // Analytics, not navigation — a tilted map makes pin sizes
                    // read as distance rather than as volume.
                    pitchWithRotate: false,
                    dragRotate: false
                });

                map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right');

                map.on('load', function () {
                    map.addSource('visitors', {
                        type: 'geojson',
                        data: {
                            type: 'FeatureCollection',
                            features: points.map(function (p) {
                                return {
                                    type: 'Feature',
                                    geometry: { type: 'Point', coordinates: [p.lng, p.lat] },
                                    properties: { events: p.events, label: p.label }
                                };
                            })
                        }
                    });

                    map.addLayer({
                        id: 'visitor-points',
                        type: 'circle',
                        source: 'visitors',
                        paint: {
                            // Interpolated on the real range, so the busiest
                            // location anchors the top of the scale whatever
                            // the window happens to contain.
                            'circle-radius': ['interpolate', ['linear'], ['get', 'events'], 1, 4, max, 26],
                            'circle-color': '#009D9A',
                            'circle-opacity': 0.55,
                            'circle-stroke-width': 1,
                            'circle-stroke-color': '#04524F'
                        }
                    });

                    var popup = new mapboxgl.Popup({ closeButton: false, closeOnClick: false });

                    map.on('mouseenter', 'visitor-points', function (e) {
                        map.getCanvas().style.cursor = 'pointer';
                        var f = e.features[0];
                        popup.setLngLat(f.geometry.coordinates)
                             .setText(f.properties.label + ' — ' + f.properties.events + ' events')
                             .addTo(map);
                    });

                    map.on('mouseleave', 'visitor-points', function () {
                        map.getCanvas().style.cursor = '';
                        popup.remove();
                    });
                });
            })();
        </script>
    @endpush
@endif
