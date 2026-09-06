{{--
    Advertiser dashboard - one screen.

    Performance used to be a second page behind its own nav item. It is here
    now, below the program table, because the two halves answer one question:
    is my advertising running, and is it doing anything. Splitting them meant
    an advertiser had to know the numbers lived somewhere else.

    Order follows what someone opens this page to find out. Standing totals
    first, then what is live and when each term ends, then what the traffic
    did over a period they choose. The period filter only governs the section
    it sits in - the tiles at the top are lifetime figures and say so - so
    changing it can never make an advertiser think their listings vanished.

    Everything geographic is labelled approximate, in the interface and not
    only in the privacy policy. It comes from an IP lookup, which places a
    visitor near their network rather than near themselves, and an unqualified
    "Orlando" invites an advertiser to believe something the data does not
    support.
--}}
@extends('layouts.member')

@section('title', 'Your advertising')
@section('crumb', 'Dashboard')

@push('head')
    @if ($mapboxToken)
        <link href="https://api.mapbox.com/mapbox-gl-js/v3.6.0/mapbox-gl.css" rel="stylesheet">
    @endif
@endpush

@section('content')

@php
    $live = $listings->where('status', \App\Enums\ListingStatus::Active);
    $totalViews = $listings->sum('views');
    $totalInquiries = $listings->sum('inquiries_count');
    $totalOffers = $listings->sum('offers_count');
@endphp

<div class="c-head">
    <div>
        <h1 class="c-head__t">Your advertising</h1>
        <p class="c-head__s">
            @if ($listings->isEmpty())
                Nothing advertised yet.
            @else
                {{ $live->count() }} of {{ $listings->count() }} {{ Str::plural('listing', $listings->count()) }} live
                @if ($member->ad_number)
                    &middot; Ad number <strong>{{ $member->ad_number }}</strong>
                    &middot;
                    <a href="{{ route('ad.member', $member->ad_number) }}" target="_blank" rel="noopener"
                       style="color:var(--c-teal-dark)">{{ config('listora.brand.domain') }}/ad/{{ $member->ad_number }}</a>
                @endif
            @endif
        </p>
    </div>
    <div class="c-head__actions">
        <a href="{{ route('owner.listings.index') }}" class="c-btn c-btn--sm">My listings</a>
    </div>
</div>

@if ($expiringSoon->isNotEmpty())
    <div class="c-note">
        <strong>{{ $expiringSoon->count() }} {{ Str::plural('listing', $expiringSoon->count()) }}</strong>
        {{ $expiringSoon->count() === 1 ? 'is' : 'are' }} near the end of the advertising term.
        Renew to keep {{ $expiringSoon->count() === 1 ? 'it' : 'them' }} visible &mdash;
        once the term ends the listing stops showing on the site, but nothing is deleted.
    </div>
@endif

{{--
    No empty state here on purpose. DashboardController::show() sends a viewer
    with no listings to the visitor dashboard instead, so this view is only
    ever reached with at least one. A "nothing here yet" branch would be
    unreachable code that reads like a handled case.
--}}
    <div class="c-tiles">
        <div class="c-tile">
            <span class="c-tile__l">Live listings</span>
            <span class="c-tile__v">{{ number_format($live->count()) }}</span>
        </div>
        <a href="{{ route('owner.inquiries.index') }}" class="c-tile {{ $unreadInquiries > 0 ? 'c-tile--urgent' : '' }}">
            <span class="c-tile__l">Unread inquiries</span>
            <span class="c-tile__v">{{ number_format($unreadInquiries) }}</span>
        </a>
        <a href="{{ route('owner.offers.index') }}" class="c-tile {{ $openOffers > 0 ? 'c-tile--urgent' : '' }}">
            <span class="c-tile__l">Open offers</span>
            <span class="c-tile__v">{{ number_format($openOffers) }}</span>
        </a>
        <div class="c-tile">
            <span class="c-tile__l">Listing views (all time)</span>
            <span class="c-tile__v">{{ number_format($totalViews) }}</span>
            <span class="c-tile__m">{{ number_format($totalInquiries) }} inquiries &middot; {{ number_format($totalOffers) }} offers</span>
        </div>
    </div>

    @if ($drafts->isNotEmpty())
        <div class="c-card" style="margin-bottom:16px">
            <div class="c-card__h">
                <h2 class="c-card__t">In review</h2>
            </div>
            <div class="c-card__b--flush c-scroll">
                <table class="c-table">
                    <thead>
                        <tr>
                            <th scope="col">Reference</th>
                            <th scope="col">Property</th>
                            <th scope="col">Program</th>
                            <th scope="col">Status</th>
                            <th scope="col">Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($drafts as $draft)
                            <tr>
                                <td><strong>{{ $draft->reference }}</strong></td>
                                <td>{{ $draft->property_name ?: $draft->club_name ?: $draft->city ?: '—' }}</td>
                                <td>{{ $draft->plan?->label() ?? '—' }}</td>
                                <td><span class="c-pill c-pill--pending">{{ $draft->status?->label() }}</span></td>
                                <td class="c-table__muted">{{ $draft->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="c-card__b" style="border-top:1px solid var(--c-line)">
                <p style="margin:0;font-size:13px;color:var(--c-ink-2)">
                    Listings stay private until our team has verified ownership. That check is what
                    every plan promises visitors, so it happens before anything goes live.
                </p>
            </div>
        </div>
    @endif

    @if ($listings->isNotEmpty())
        <div class="c-card" style="margin-bottom:16px">
            <div class="c-card__h">
                <h2 class="c-card__t">Advertising program</h2>
                <a href="{{ route('owner.listings.index') }}" class="c-card__link">Manage</a>
            </div>
            <div class="c-card__b--flush c-scroll">
                <table class="c-table">
                    <thead>
                        <tr>
                            <th scope="col">Property</th>
                            <th scope="col">Program</th>
                            <th scope="col">Status</th>
                            <th scope="col">Live since</th>
                            <th scope="col">Term ends</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listings as $listing)
                            @php
                                $isLive = $listing->status === \App\Enums\ListingStatus::Active;
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('owner.listings.edit', $listing) }}"><strong>{{ $listing->title }}</strong></a>
                                    <div class="c-table__muted">{{ $listing->reference }}</div>
                                </td>
                                <td>{{ $listing->plan?->label() ?? '—' }}</td>
                                <td>
                                    <span class="c-pill {{ $isLive ? 'c-pill--live' : 'c-pill--off' }}">
                                        {{ $listing->status?->label() ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="c-table__muted">{{ $listing->published_at?->format('j M Y') ?? '—' }}</td>
                                <td class="c-table__muted">{{ $listing->expires_at?->format('j M Y') ?? 'No end date' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="c-head">
            <div>
                <h2 class="c-head__t" style="font-size:17px">Advertising performance</h2>
                <p class="c-head__s">How each listing is doing.</p>
            </div>
        </div>

        <div class="c-lcards">
            @foreach ($listings as $listing)
                @php
                    $photo = is_array($listing->photos) ? ($listing->photos[0] ?? null) : null;
                    $isLive = $listing->status === \App\Enums\ListingStatus::Active;
                @endphp
                <a href="{{ route('owner.listings.edit', $listing) }}" class="c-lcard">
                    <div class="c-lcard__img" @if ($photo) style="background-image:url('{{ $photo }}')" @endif>
                        <span class="c-pill {{ $isLive ? 'c-pill--live' : 'c-pill--off' }} c-lcard__pill">
                            {{ $listing->status?->label() ?? 'Unknown' }}
                        </span>
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

        {{-- ------------------------------------------------------------------
             Traffic, over a period the advertiser chooses.

             Everything from here down is scoped by the filter directly below.
             The heading says the period out loud so a figure read halfway down
             the page is never mistaken for a lifetime total.
        ------------------------------------------------------------------ --}}
        <div class="c-head" style="margin-top:28px">
            <div>
                <h2 class="c-head__t" style="font-size:17px">Traffic and engagement</h2>
                <p class="c-head__s">
                    What happened {{ $rangeLabel }}@if ($selectedListing), for {{ $selectedListing->title }}@endif.
                </p>
            </div>
        </div>

        {{-- A plain GET form: these are shareable, bookmarkable views of
             someone's own numbers, and a filter that only lives in JavaScript
             state cannot be sent to anyone. --}}
        <form method="GET" action="{{ route('dashboard') }}" class="c-card" style="margin-bottom:16px">
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
                        @foreach ($perfListings as $option)
                            <option value="{{ $option->id }}" @selected($selectedListing?->id === $option->id)>
                                {{ Str::limit($option->title, 44) }}
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
                <h2 class="c-card__t">Engagement</h2>
                <span class="c-card__link" style="color:var(--c-ink-3);cursor:default">How far people got</span>
            </div>
            <div class="c-card__b--flush">
                @php $funnelTop = max(1, $funnel[0]['count'] ?? 1); @endphp
                <table class="c-table">
                    <tbody>
                        @foreach ($funnel as $step)
                            <tr>
                                <td style="width:230px">{{ $step['label'] }}</td>
                                <td>
                                    {{-- A bar, not a chart library: one number relative to
                                         the step above it is the entire message. --}}
                                    <div style="background:var(--c-surface-2);border-radius:var(--c-r-pill);height:9px;overflow:hidden">
                                        <div style="width:{{ round(($step['count'] / $funnelTop) * 100, 1) }}%;height:100%;background:var(--c-teal)"></div>
                                    </div>
                                </td>
                                <td class="c-table__num" style="width:90px">{{ number_format($step['count']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
