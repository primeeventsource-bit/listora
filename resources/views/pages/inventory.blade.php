@extends('layouts.app')

@section('title', 'Advertised properties — Listora')
@section('meta', 'The current Listora inventory: the vacation properties owners are advertising right now, listed as facts. Contact any owner directly. Listora advertises only — no bookings, no commission.')

@section('content')

<div class="page-head plain">
    <div class="wrap">
        <span class="eyebrow">{{ number_format($total) }} live {{ Str::plural('listing', $total) }}</span>
        <h1>Advertised properties</h1>
        <p>
            What owners are advertising right now, written down plainly. Ten most recently
            published below — every one verified before it went up, and every one answered
            by the person who owns it.
        </p>
    </div>
</div>

<section class="pad-sm">
    <div class="wrap">

        <div class="stat-row" style="margin-bottom:34px">
            <div class="stat">
                <span class="stat-n tnum">{{ number_format($total) }}</span>
                <span class="stat-l">Live listings</span>
            </div>
            <div class="stat">
                <span class="stat-n tnum">{{ number_format($byKind[\App\Models\Listing::KIND_HOME] ?? 0) }}</span>
                <span class="stat-l">Vacation properties</span>
            </div>
            {{-- Counts for withheld categories are not shown while
                 timeshare_categories is off: the listings behind them are not
                 public, and a register that totals what it does not list is
                 telling the reader about inventory they cannot see. --}}
            @if (feature('timeshare_categories', null, false))
                <div class="stat">
                    <span class="stat-n tnum">{{ number_format($byKind[\App\Models\Listing::KIND_POINTS] ?? 0) }}</span>
                    <span class="stat-l">Resort club points</span>
                </div>
                <div class="stat">
                    <span class="stat-n tnum">{{ number_format($byKind[\App\Models\Listing::KIND_WEEKS] ?? 0) }}</span>
                    <span class="stat-l">Vacation weeks</span>
                </div>
            @endif
            <div class="stat">
                <span class="stat-n tnum">{{ number_format($regions) }}</span>
                <span class="stat-l">Regions represented</span>
            </div>
        </div>

        @if ($listings->count())
            <div class="toolbar">
                <div class="n">
                    <b>{{ $listings->count() }}</b> most recently published
                </div>
                <a href="{{ route('listings.index') }}" class="btn btn-outline btn-sm">Search all {{ number_format($total) }} &rarr;</a>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Reference</th>
                            <th scope="col">Listing</th>
                            <th scope="col">Type</th>
                            <th scope="col">Rent or own</th>
                            <th scope="col">Detail</th>
                            <th scope="col">Asking</th>
                            <th scope="col">Plan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listings as $listing)
                            <tr>
                                <td>
                                    <code>{{ $listing->reference }}</code>
                                    @if ($listing->is_verified)
                                        <div style="margin-top:6px"><span class="pill">Verified</span></div>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ $listing->publicUrl() }}" style="font-weight:600">{{ $listing->title }}</a>
                                    <div style="color:var(--slate);font-size:13.5px;margin-top:4px">
                                        {{ $listing->location }}
                                        @if ($listing->resort_name)
                                            &middot; {{ $listing->resort_name }}
                                        @elseif ($listing->club_name)
                                            &middot; {{ $listing->club_name }}
                                        @endif
                                    </div>
                                </td>

                                <td>{{ $listing->kind_label }}</td>

                                <td>{{ $listing->mode === 'rent' ? 'Rent' : 'Own' }}</td>

                                <td>
                                    {{ $listing->key_fact }}
                                    @if ($listing->usage)
                                        <div style="color:var(--slate);font-size:13.5px;margin-top:4px">{{ $listing->usage }}</div>
                                    @endif
                                </td>

                                <td style="white-space:nowrap">
                                    <b>{{ $listing->price_formatted }}</b>
                                    @if ($listing->price_unit_label)
                                        <span style="color:var(--slate)">{{ $listing->price_unit_label }}</span>
                                    @endif
                                    @if ($listing->total_price)
                                        <div style="color:var(--slate);font-size:13px;margin-top:4px">
                                            &asymp; ${{ number_format($listing->total_price) }} all-in
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <span class="pill {{ $listing->plan?->isFeatured() ? '' : 'pill-off' }}">
                                        {{ $listing->plan?->label() ?? 'Essential' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{--
                The one thing a register like this must not imply. A table of
                prices reads like a price list you can act on, so the limit is
                stated where the prices are, not only in the footer.
            --}}
            <p class="muted" style="margin-top:20px;max-width:70ch">
                Every figure above is what the owner is asking, not a quote and not a total.
                Listora advertises these listings and nothing more — send an inquiry or an
                offer and you settle dates, price, and terms with the owner directly.
                Nothing on this page holds a date or takes a payment.
            </p>

            <div style="margin-top:28px;display:flex;gap:14px;flex-wrap:wrap">
                <a href="{{ route('listings.index') }}" class="btn btn-primary">Explore every listing</a>
                <a href="{{ route('list.create') }}" class="btn btn-outline">Advertise yours</a>
            </div>
        @else
            <div class="empty">
                <h3 style="font-size:26px;margin-bottom:12px">Nothing is published yet</h3>
                <p>Listings appear here once ownership has been verified and the listing goes live.</p>
                <a href="{{ route('list.create') }}" class="btn btn-primary" style="margin-top:12px">Advertise yours</a>
            </div>
        @endif

    </div>
</section>

@endsection
