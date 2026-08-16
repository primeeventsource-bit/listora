@php
    $hero = $listings->first();
    $rows = $listings->skip(1)->take(3);
@endphp

<div class="phone-stack">
    <div class="phone">
        <div class="notch"></div>
        <div class="screen">
            @if ($hero)
                <div class="ph-hero">
                    <img src="{{ $hero->photoUrl(0, 600, 700) }}" alt="" loading="lazy">
                    <div class="cap">
                        <div class="s">{{ $hero->location }}</div>
                        <div class="t">{{ Str::limit($hero->title, 42) }}</div>
                    </div>
                </div>
            @endif

            <div class="ph-search">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg>
                Search destinations, resorts, clubs
            </div>

            <div class="ph-body" style="padding-top:0">
                @foreach ($rows as $r)
                    <div class="ph-row">
                        <span class="ph-thumb"><img src="{{ $r->photoUrl(1, 200, 170) }}" alt="" loading="lazy"></span>
                        <span>
                            <span class="t">{{ Str::limit($r->title, 26) }}</span>
                            <span class="s">{{ $r->kind_label }} &middot; {{ $r->city }}</span>
                        </span>
                        <span class="p">{{ $r->price_formatted }}</span>
                    </div>
                @endforeach
            </div>

            <div class="ph-tabbar">
                <span class="act"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10.5 12 3l9 7.5V21H3z"/></svg></span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg></span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 5.6a5 5 0 0 0-7.1 0L12 7.3l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 21.5l8.8-8.8a5 5 0 0 0 0-7.1z"/></svg></span>
                <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4L3 21l1.1-3.4A8.4 8.4 0 1 1 21 11.5z"/></svg></span>
            </div>
        </div>
    </div>
</div>
