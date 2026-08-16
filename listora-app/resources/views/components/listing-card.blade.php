@props(['listing'])

<article class="card reveal">
    <div class="card-media">
        <img src="{{ $listing->photoUrl(0, 900, 675) }}"
             alt="{{ $listing->title }}, {{ $listing->location }}"
             loading="lazy" width="900" height="675">

        <div class="card-tags">
            <span class="tag teal">{{ $listing->kind_label }}</span>
            @if ($listing->is_featured)
                <span class="tag amber">Featured</span>
            @endif
        </div>

        <button class="save" type="button" aria-label="Save this listing">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 21.5l8.8-8.8a5 5 0 0 0 0-7.1z"/>
            </svg>
        </button>
    </div>

    <div class="card-body">
        <span class="card-loc">{{ $listing->location }}</span>
        <h3><a href="{{ route('listings.show', $listing) }}" class="stretch">{{ $listing->title }}</a></h3>

        <p class="card-fact">
            <b>{{ $listing->key_fact }}</b>
            @if ($listing->resort_name) &middot; {{ $listing->resort_name }} @endif
        </p>

        <div class="card-foot">
            <div>
                <span class="price">{{ $listing->price_formatted }}<small>{{ $listing->price_unit_label }}</small></span>
                @if ($listing->total_price)
                    <div style="font-size:12.5px;color:var(--slate);margin-top:5px">
                        ≈ ${{ number_format($listing->total_price) }} for all {{ number_format($listing->points) }} points
                    </div>
                @endif
            </div>
            <div class="card-owner">
                @if ($listing->is_verified)
                    <span style="color:var(--teal);font-weight:600">Verified owner</span><br>
                @endif
                Replies {{ $listing->response_time }}
            </div>
        </div>
    </div>
</article>
