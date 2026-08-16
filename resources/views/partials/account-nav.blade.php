@php
    $user = auth()->user();

    // Built from what the user can actually reach, not from a fixed list —
    // a link to a screen that 403s is worse than no link.
    $links = [['dashboard', 'Overview']];

    if ($user?->isStaff()) {
        // Gated on the same permission each route requires, now that RbacSeeder
        // has made those checks real. Before RBAC was seeded every check fell
        // back to "is admin", so a Listing Specialist was shown Users and
        // Settings and got a 403 on both — which the comment above says is
        // worse than no link, and was true the whole time.
        $staffLinks = [
            ['admin.drafts.index',   'Review queue', 'drafts.view'],
            ['admin.listings.index', 'Listings',     'listings.view'],
            ['admin.offers.index',   'Offers',       'offers.view'],
            ['admin.inbox.index',    'Inbox',        'inbox.view'],
            ['admin.users.index',    'Users',        'users.view'],
            ['admin.roles.index',    'Roles',        'roles.view'],
            ['admin.settings.index', 'Settings',     'settings.view'],
            ['admin.audit.index',    'Activity log', 'audit.view'],
        ];

        foreach ($staffLinks as [$route, $label, $permission]) {
            if ($user->hasPermission($permission)) {
                $links[] = [$route, $label];
            }
        }
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
