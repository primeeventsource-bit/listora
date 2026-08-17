@php
    // Help replaced Contact. The old tab was a bare mailto: — it opened a
    // blank email client and left the visitor to work out what to say, with
    // no answers in between. Help is a real page: search, an AI assistant,
    // a question form that keeps a reference, and the contact details.
    $nav = [
        ['listings.index', 'Explore'],
        ['list.create',    'Advertise'],
        ['how',            'How It Works'],
        ['pricing',        'Pricing'],
        ['about',          'About'],
        ['help.index',     'Help'],
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
            </div>

            <div class="nav-actions">
                {{--
                    This pointed at route('listings.index') — the Sign In button
                    sent people to the browse page, so the site had no working
                    way in at all. Signed-in visitors get their dashboard here
                    instead; offering "Sign In" to someone already signed in is
                    the other half of the same mistake.
                --}}
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm hide-sm">Dashboard</a>

                    {{-- A form, not a link: logout is a POST and must carry the
                         CSRF token. A GET sign-out can be triggered by any image
                         tag on any page and would log people out at random. --}}
                    <form method="POST" action="{{ route('logout') }}" class="nav-signout">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline btn-sm hide-sm">Sign In</a>
                    <a href="{{ route('list.create') }}" class="btn btn-primary btn-sm">Get Started</a>
                @endauth
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

    {{-- The desktop button carries `hide-sm`, so without these a phone had no
         way to sign in at all. --}}
    @auth
        <a href="{{ route('dashboard') }}">Dashboard</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-link">Sign out</button>
        </form>
    @else
        <a href="{{ route('login') }}">Sign In</a>
        <a href="{{ route('list.create') }}">Get Started</a>
    @endauth
</div>
