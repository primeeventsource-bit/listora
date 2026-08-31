{{--
    Member shell - what an advertiser sees.

    Shares console.css with the staff console on purpose. That is one Listora
    application design system, not two: an advertiser who is also staff should
    not feel they have changed products when they cross between /account and
    /admin. What differs is the navigation and what the screens are for - this
    rail carries an advertiser's own work, never anything operational.

    Deliberately not layouts/app.blade.php. The member area used to render
    inside the marketing site, so a paying advertiser managing their listings
    was looking at the same header that tries to sell them a plan.
--}}
@php
    $u = auth()->user();
    $initials = collect(explode(' ', trim($u?->name ?? '?')))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'My advertising') &middot; Listora</title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0D1B2A">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset_v('css/console.css') }}">
<link rel="icon" href="{{ asset('img/favicon.svg') }}" type="image/svg+xml">
@stack('head')
</head>
<body class="console">

<div class="c-shell" data-nav="closed" id="shell">

    <div class="c-scrim" data-nav-close></div>

    <aside class="c-rail">
        <div class="c-rail__brand">
            <span class="c-rail__mark">L</span>
            <span class="c-rail__name">Listora</span>
        </div>

        <nav class="c-nav" aria-label="My account">
            @php
                $groups = [
                    'Overview' => [
                        ['dashboard', 'Dashboard', 'grid', null],
                    ],
                    'My advertising' => [
                        ['owner.listings.index', 'My listings', 'building', null],
                        ['owner.performance', 'Performance', 'chart', null],
                    ],
                    'Interest' => [
                        ['owner.inquiries.index', 'Inquiries', 'chat', $memberInquiries ?? null],
                        ['owner.offers.index', 'Offers', 'tag', $memberOffers ?? null],
                    ],
                    'Account' => [
                        ['profile.edit', 'Profile &amp; settings', 'cog', null],
                    ],
                ];
                $icons = [
                    'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
                    'building' => '<path d="M4 21V6a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v15"/><path d="M14 10h5a1 1 0 0 1 1 1v10"/><path d="M2 21h20"/><path d="M8 9h2M8 13h2M8 17h2"/>',
                    'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
                    'chat'     => '<path d="M20 12a7 7 0 0 1-7 7H8l-4 3v-4.5A7 7 0 0 1 4 12v-1a7 7 0 0 1 7-7h2a7 7 0 0 1 7 7z"/>',
                    'tag'      => '<path d="M3 11V5a2 2 0 0 1 2-2h6l9 9-8 8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
                    'cog'      => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/>',
                ];
            @endphp

            @foreach ($groups as $label => $items)
                <div class="c-nav__group">
                    <div class="c-nav__label">{{ $label }}</div>
                    @foreach ($items as [$route, $text, $icon, $badge])
                        <a href="{{ route($route) }}" class="c-nav__link"
                           @if (request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route))) aria-current="page" @endif>
                            <svg class="c-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$icon] !!}</svg>
                            <span>{!! $text !!}</span>
                            @if ($badge)
                                <span class="c-nav__count c-nav__count--urgent">{{ $badge }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        <div class="c-rail__foot">
            <div class="c-who">
                <span class="c-who__av">{{ $initials ?: '?' }}</span>
                <span>
                    <span class="c-who__name">{{ $u?->name }}</span><br>
                    <span class="c-who__role">Advertiser</span>
                </span>
            </div>
        </div>
    </aside>

    <div class="c-main">
        <header class="c-top">
            <button class="c-burger" type="button" data-nav-open aria-label="Open navigation">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
            <div class="c-top__crumb"><b>@yield('crumb', 'Dashboard')</b></div>
            <div class="c-top__actions">
                <a href="{{ route('list.create') }}" class="c-btn c-btn--primary c-btn--sm">Advertise a property</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="c-btn c-btn--sm">Sign out</button>
                </form>
            </div>
        </header>

        <main class="c-body">
            <div class="c-body__inner">
                @yield('content')
            </div>
        </main>
    </div>
</div>

<script>
(function () {
    var shell = document.getElementById('shell');
    if (!shell) return;
    var set = function (state) { shell.setAttribute('data-nav', state); };
    document.querySelectorAll('[data-nav-open]').forEach(function (el) {
        el.addEventListener('click', function () { set('open'); });
    });
    document.querySelectorAll('[data-nav-close]').forEach(function (el) {
        el.addEventListener('click', function () { set('closed'); });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set('closed'); });
})();
</script>
@stack('scripts')
</body>
</html>
