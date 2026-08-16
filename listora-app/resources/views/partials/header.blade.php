@php
    $nav = [
        ['listings.index', 'Explore'],
        ['list.create',    'Advertise'],
        ['how',            'How It Works'],
        ['pricing',        'Pricing'],
        ['about',          'About'],
    ];
@endphp

<header class="site-header {{ $overPhoto ? 'over-photo' : '' }}" id="siteHeader">
    <div class="wrap">
        <nav class="nav">
            <a href="{{ route('home') }}" class="brand" aria-label="Listora — list, connect, explore">
                @include('partials.logo')
                <span class="wordmark">List<span class="o">o</span>ra</span>
            </a>

            <div class="nav-links">
                @foreach ($nav as [$route, $label])
                    <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
                <a href="mailto:{{ config('listora.brand.email') }}">Contact</a>
            </div>

            <div class="nav-actions">
                <a href="{{ route('listings.index') }}" class="btn btn-outline btn-sm hide-sm">Sign In</a>
                <a href="{{ route('list.create') }}" class="btn btn-primary btn-sm">Get Started</a>
                <button class="burger" id="burger" aria-label="Open menu" aria-expanded="false">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M3 6h18M3 12h18M3 18h18"/>
                    </svg>
                </button>
            </div>
        </nav>
    </div>
</header>

<div class="mobile-nav" id="mobileNav">
    @foreach ($nav as [$route, $label])
        <a href="{{ route($route) }}">{{ $label }}</a>
    @endforeach
    <a href="mailto:{{ config('listora.brand.email') }}">Contact</a>
    <a href="{{ route('list.create') }}">Get Started</a>
</div>
