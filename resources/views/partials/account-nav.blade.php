@php
    $user = auth()->user();

    // Built from what the user can actually reach, not from a fixed list —
    // a link to a screen that 403s is worse than no link.
    $links = [['dashboard', 'Overview']];

    if ($user?->isStaff()) {
        $links[] = ['admin.drafts.index', 'Review queue'];
        $links[] = ['admin.listings.index', 'Listings'];
        $links[] = ['admin.offers.index', 'Offers'];
        $links[] = ['admin.inbox.index', 'Inbox'];
        $links[] = ['admin.users.index', 'Users'];
        $links[] = ['admin.settings.index', 'Settings'];
    } else {
        $links[] = ['owner.listings.index', 'My listings'];
        $links[] = ['owner.offers.index', 'Offers'];
        $links[] = ['owner.inquiries.index', 'Inquiries'];
    }

    $links[] = ['profile.edit', 'Profile'];
@endphp

<nav class="account-nav">
    <div class="wrap">
        @foreach ($links as [$route, $label])
            <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach

        <form method="POST" action="{{ route('logout') }}" class="account-nav-out">
            @csrf
            <button type="submit" class="btn-link">Sign out</button>
        </form>
    </div>
</nav>
