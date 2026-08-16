@extends('layouts.app')

@section('title', $seo->title())
@section('meta', $seo->description())
@section('robots', $seo->robots())

@section('head')
    <link rel="canonical" href="{{ $seo->canonical() }}">

    @if ($listings->currentPage() > 1)
        <link rel="prev" href="{{ $listings->previousPageUrl() }}">
    @endif
    @if ($listings->hasMorePages())
        <link rel="next" href="{{ $listings->nextPageUrl() }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Listora">
    <meta property="og:url" content="{{ $seo->canonical() }}">
    <meta property="og:title" content="{{ $seo->title() }}">
    <meta property="og:description" content="{{ $seo->description() }}">
    @if ($listings->count() && $listings->first()->photoUrl(0, 1200, 630))
        <meta property="og:image" content="{{ $listings->first()->photoUrl(0, 1200, 630) }}">
    @elseif (setting('seo.og_image_url'))
        <meta property="og:image" content="{{ setting('seo.og_image_url') }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo->title() }}">
    <meta name="twitter:description" content="{{ $seo->description() }}">

    <script type="application/ld+json">{!! $seo->jsonLd() !!}</script>
@endsection

@push('analytics')
    // Google Ads remarketing + GA4 merchandising for this facet. Fires with the
    // listings actually rendered, so an audience built here matches what the
    // visitor was shown rather than what the whole catalogue contains.
    gtag('event', 'view_item_list', @json($seo->itemListPayload()));
@endpush

@section('content')

<div class="page-head photo">
    <img class="bg" src="https://images.unsplash.com/photo-1520277872024-58b40679ddb4?auto=format&fit=crop&w=2000&h=800&q=75" alt="" loading="eager">
    <div class="wrap">
        <span class="eyebrow">{{ number_format($listings->total()) }} live {{ Str::plural('listing', $listings->total()) }}</span>
        {{-- Facet-aware, and must stay in step with $seo->title() — see ExploreSeo::heading(). --}}
        <h1>{{ $seo->heading() }}</h1>
        <p>Vacation properties, resort club points, and vacation weeks — all published and answered by the people who own them.</p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">
        <div class="browse">

            {{-- ------------------------------- filters ------------------------------ --}}
            <form class="filters" method="GET" action="{{ route('listings.index') }}" id="filterForm">
                @if ($filters['q'])
                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                @endif

                <div class="fgroup">
                    <h4>Listing type</h4>
                    <label class="fopt {{ $filters['kind'] === 'all' ? 'checked' : '' }}">
                        <input type="radio" name="kind" value="all" @checked($filters['kind'] === 'all')> All
                        <span class="c">{{ $facets['kind']->sum() }}</span>
                    </label>
                    @foreach (\App\Models\Listing::KINDS as $value => $label)
                        <label class="fopt {{ $filters['kind'] === $value ? 'checked' : '' }}">
                            <input type="radio" name="kind" value="{{ $value }}" @checked($filters['kind'] === $value)> {{ $label }}
                            <span class="c">{{ $facets['kind'][$value] ?? 0 }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="fgroup">
                    <h4>Rent or own</h4>
                    <label class="fopt {{ $filters['mode'] === 'rent' ? 'checked' : '' }}">
                        <input type="radio" name="mode" value="rent" @checked($filters['mode'] === 'rent')> Available to rent
                        <span class="c">{{ $facets['mode']['rent'] ?? 0 }}</span>
                    </label>
                    <label class="fopt {{ $filters['mode'] === 'own' ? 'checked' : '' }}">
                        <input type="radio" name="mode" value="own" @checked($filters['mode'] === 'own')> Available to own
                        <span class="c">{{ $facets['mode']['own'] ?? 0 }}</span>
                    </label>
                </div>

                <div class="fgroup">
                    <h4>Region</h4>
                    @foreach ($facets['region'] as $region => $count)
                        <label class="fopt {{ $filters['region'] === $region ? 'checked' : '' }}">
                            <input type="radio" name="region" value="{{ $region }}" @checked($filters['region'] === $region)> {{ $region }}
                            <span class="c">{{ $count }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="fgroup">
                    <h4>Bedrooms</h4>
                    <label class="fopt {{ ! $filters['beds'] ? 'checked' : '' }}">
                        <input type="radio" name="beds" value="" @checked(! $filters['beds'])> Any
                    </label>
                    @foreach ([1, 2, 3, 4] as $b)
                        <label class="fopt {{ (int) $filters['beds'] === $b ? 'checked' : '' }}">
                            <input type="radio" name="beds" value="{{ $b }}" @checked((int) $filters['beds'] === $b)> {{ $b }}+ bedrooms
                        </label>
                    @endforeach
                </div>

                <div class="fgroup">
                    <a href="{{ route('listings.index') }}" class="btn btn-outline btn-sm btn-block">Clear all filters</a>
                </div>
            </form>

            {{-- ------------------------------- results ------------------------------ --}}
            <div>
                <div class="toolbar">
                    <div class="n">
                        <b>{{ $listings->total() }}</b> {{ Str::plural('listing', $listings->total()) }}
                        @if ($filters['q']) matching “{{ $filters['q'] }}” @endif
                    </div>
                    <form method="GET" action="{{ route('listings.index') }}">
                        @foreach (['q', 'kind', 'mode', 'region', 'beds'] as $k)
                            @if ($filters[$k] && $filters[$k] !== 'all')
                                <input type="hidden" name="{{ $k }}" value="{{ $filters[$k] }}">
                            @endif
                        @endforeach
                        <select class="sel" name="sort" onchange="this.form.submit()">
                            <option value="recommended" @selected($filters['sort'] === 'recommended')>Recommended</option>
                            <option value="newest"      @selected($filters['sort'] === 'newest')>Newest first</option>
                            <option value="price_low"   @selected($filters['sort'] === 'price_low')>Price: low to high</option>
                            <option value="price_high"  @selected($filters['sort'] === 'price_high')>Price: high to low</option>
                            <option value="bedrooms"    @selected($filters['sort'] === 'bedrooms')>Most bedrooms</option>
                        </select>
                    </form>
                </div>

                @if ($listings->count())
                    <div class="grid g3">
                        @foreach ($listings as $listing)
                            <x-listing-card :listing="$listing"/>
                        @endforeach
                    </div>

                    @if ($listings->hasPages())
                        <div class="pager">
                            @if ($listings->onFirstPage())
                                <span class="off">Previous</span>
                            @else
                                <a href="{{ $listings->previousPageUrl() }}">Previous</a>
                            @endif

                            @foreach (range(1, $listings->lastPage()) as $p)
                                @if ($p === $listings->currentPage())
                                    <span class="on">{{ $p }}</span>
                                @else
                                    <a href="{{ $listings->url($p) }}">{{ $p }}</a>
                                @endif
                            @endforeach

                            @if ($listings->hasMorePages())
                                <a href="{{ $listings->nextPageUrl() }}">Next</a>
                            @else
                                <span class="off">Next</span>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="empty">
                        <h3 style="font-size:26px;margin-bottom:12px">Nothing matches that yet</h3>
                        <p>Try widening the region or clearing a filter — new listings publish every day.</p>
                        <a href="{{ route('listings.index') }}" class="btn btn-primary" style="margin-top:12px">Clear filters</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    document.querySelectorAll('#filterForm input[type=radio]').forEach(function (el) {
        el.addEventListener('change', function () { document.getElementById('filterForm').submit(); });
    });
</script>
@endpush
