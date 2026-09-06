{{--
    Console shell.

    Standalone on purpose. The admin screens used to render inside
    layouts/app.blade.php - the marketing layout, complete with the public
    header, the footer, and the Poppins marketing type scale. That made the
    console look like a logged-in version of the website rather than a tool,
    and it meant every change to the public header risked moving furniture
    inside the console.

    Nav is built from the viewer's permissions, never from isStaff(). A link
    the viewer cannot open is not rendered at all, because a nav that lists a
    section and then 403s on click tells the viewer the section exists and
    that they are not trusted with it - which is a disclosure and an
    irritation at the same time.
--}}
@php
    $u = auth()->user();
    $can = fn (string $p) => $u && $u->hasPermission($p);
    $initials = collect(explode(' ', trim($u?->name ?? '?')))
        ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    /*
     | Screens carried over from the public layout title themselves
     | "Users — Listora", because that layout rendered the brand nowhere else.
     | This one appends the brand itself, so the suffix is stripped rather
     | than each of nineteen views being edited to drop it - otherwise every
     | tab reads "Users — Listora · Listora".
     |
     | The cleaned value also stands in for the breadcrumb, so a screen that
     | sets no @section('crumb') names itself in the topbar instead of
     | claiming to be the Dashboard.
     */
    $pageTitle = trim(preg_replace(
        '/\s*[—–-]\s*Listora\s*$/u',
        '',
        trim($__env->yieldContent('title', 'Console'))
    ));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $pageTitle }} &middot; Listora</title>
{{-- The console is never indexed. Not narrowable by child views. --}}
<meta name="robots" content="noindex, nofollow, noarchive">
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
            @if (config('app.env') !== 'production')
                <span class="c-rail__env">{{ config('app.env') }}</span>
            @endif
        </div>

        <nav class="c-nav" aria-label="Console">
            @php
                // route name => [label, icon key, permission (null = always), badge]
                $groups = [
                    'Overview' => [
                        ['dashboard', 'Dashboard', 'grid', null, null],
                    ],
                    'Advertising' => [
                        ['admin.listings.index', 'Listings', 'building', 'listings.view', null],
                        ['admin.drafts.index', 'Submissions', 'inbox-in', 'drafts.view', $navDrafts ?? null],
                    ],
                    'Demand' => [
                        ['admin.inbox.index', 'Inquiries', 'chat', 'inbox.view', $navInquiries ?? null],
                        ['admin.offers.index', 'Offers', 'tag', 'offers.view', $navOffers ?? null],
                    ],
                    'Members' => [
                        ['admin.users.index', 'Customers', 'users', 'users.view', null],
                    ],
                    'Insight' => [
                        ['admin.reports.index', 'Performance', 'chart', 'reports.view', null],
                        ['admin.advertising.index', 'Ad traffic', 'pulse', 'advertising.trace', null],
                        // Two different logs, named for what each one holds.
                        // "Activity" pointed at the admin change trail while
                        // the visitor trail had no entry at all, so the entry
                        // most people wanted was the one they could not find.
                        ['admin.activity.index', 'Visitor activity', 'pulse', 'activity.view', null],
                        ['admin.audit.index', 'Admin changes', 'shield', 'audit.view', null],
                    ],
                    'System' => [
                        ['admin.roles.index', 'Roles &amp; permissions', 'shield', 'roles.view', null],
                        ['admin.settings.index', 'Settings', 'cog', 'settings.view', null],
                    ],
                ];
                $icons = [
                    'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
                    'building' => '<path d="M4 21V6a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v15"/><path d="M14 10h5a1 1 0 0 1 1 1v10"/><path d="M2 21h20"/><path d="M8 9h2M8 13h2M8 17h2"/>',
                    'inbox-in' => '<path d="M4 13h4l1.5 3h5L16 13h4"/><path d="M4 13V6a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v7v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/>',
                    'chat'     => '<path d="M20 12a7 7 0 0 1-7 7H8l-4 3v-4.5A7 7 0 0 1 4 12v-1a7 7 0 0 1 7-7h2a7 7 0 0 1 7 7z"/>',
                    'tag'      => '<path d="M3 11V5a2 2 0 0 1 2-2h6l9 9-8 8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
                    'users'    => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 5.5a3 3 0 0 1 0 5.6"/><path d="M18 20a5.6 5.6 0 0 0-2-4.3"/>',
                    'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
                    'pulse'    => '<path d="M2 12h4l3-8 4 16 3-8h6"/>',
                    'shield'   => '<path d="M12 3l8 3v6c0 4.5-3.2 7.9-8 9-4.8-1.1-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
                    'cog'      => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/>',
                ];
            @endphp

            @foreach ($groups as $label => $items)
                @php
                    $visible = array_filter($items, fn ($i) => $i[3] === null || $can($i[3]));
                @endphp
                @if ($visible)
                    <div class="c-nav__group">
                        <div class="c-nav__label">{{ $label }}</div>
                        @foreach ($visible as [$route, $text, $icon, $perm, $badge])
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
                @endif
            @endforeach
        </nav>

        <div class="c-rail__foot">
            <div class="c-who">
                <span class="c-who__av">{{ $initials ?: '?' }}</span>
                <span>
                    <span class="c-who__name">{{ $u?->name }}</span><br>
                    <span class="c-who__role">{{ $u?->role?->label() ?? 'Staff' }}</span>
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
            <div class="c-top__crumb"><b>@yield('crumb', $pageTitle)</b></div>
            <div class="c-top__actions">
                <a href="{{ url('/') }}" class="c-btn c-btn--ghost c-btn--sm" target="_blank" rel="noopener">View site</a>
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
// Mobile rail. Kept inline and tiny rather than pulled into listora.js, which
// is the public site's bundle and has no reason to ship to the console.
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
