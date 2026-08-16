<?php

namespace Tests\Feature\Rbac;

use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Enforces the rule stated at the top of the admin group in routes/web.php.
 *
 * That comment says every admin route must carry a `permission:` middleware,
 * and that a route added without one is reachable by any signed-in user. The
 * rule was written down and then nothing checked it, which is the state in
 * which a rule quietly stops being true — the group's outer middleware is only
 * `auth`, so an ungated route inside it is protected by convention alone.
 *
 * This is a structural test: it walks the router rather than exercising
 * behaviour, so a route added next year is covered without anyone remembering
 * this file exists.
 */
class RbacPermissionCoverageTest extends TestCase
{
    /**
     * Routes inside the admin group that legitimately carry no permission.
     *
     * Anything added here needs a reason, because each entry is a hole in the
     * rule above.
     */
    private const EXEMPT = [
        // Redirects to /dashboard, which decides what to show from who the
        // user is. A traveler following it lands on their own dashboard and
        // learns nothing about the console, so there is no capability to gate.
        'admin.index',
    ];

    public function test_every_admin_route_carries_a_permission_gate(): void
    {
        $ungated = [];

        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            $uri = $route->uri();

            $isAdmin = str_starts_with($uri, 'admin')
                || str_starts_with($uri, 'api/v1/admin');

            if (! $isAdmin || in_array($name, self::EXEMPT, true)) {
                continue;
            }

            $middleware = implode(' ', array_filter($route->gatherMiddleware(), 'is_string'));

            if (! str_contains($middleware, 'permission:')) {
                $ungated[] = implode('|', $route->methods()).' /'.$uri.'  ('.($name ?: 'unnamed').')';
            }
        }

        $this->assertSame([], $ungated, implode("\n", [
            'These admin routes have no permission: middleware and are therefore',
            'reachable by ANY signed-in user, including a traveler:',
            '',
            ...$ungated,
            '',
            'Add a permission from App\Support\PermissionCatalog, or add the route',
            'name to self::EXEMPT with a reason.',
        ]));
    }

    /**
     * A typo'd key does not fail loudly — EnsurePermission simply never
     * matches it, so the route 403s for everyone including a super admin, who
     * bypasses the check and never notices.
     */
    public function test_every_permission_used_on_a_route_exists_in_the_catalog(): void
    {
        $catalog = PermissionCatalog::keys();
        $unknown = [];

        foreach (Route::getRoutes() as $route) {
            $middleware = implode(' ', array_filter($route->gatherMiddleware(), 'is_string'));

            if (! preg_match_all('/permission:([a-z_.,]+)/', $middleware, $matches)) {
                continue;
            }

            foreach ($matches[1] as $group) {
                foreach (array_filter(explode(',', $group)) as $key) {
                    if (! in_array($key, $catalog, true)) {
                        $unknown[] = $key.'  (/'.$route->uri().')';
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($unknown)),
            "Routes reference permissions that PermissionCatalog does not define:\n"
            .implode("\n", array_unique($unknown)));
    }

    /**
     * The catalog is the allow-list the role editor renders from. A permission
     * nothing uses is either a missing gate somewhere or dead vocabulary, and
     * both are worth seeing — reported, not failed, since a key may legitimately
     * be checked in Blade or a policy rather than on a route.
     */
    public function test_it_reports_catalog_keys_no_route_uses(): void
    {
        $used = [];

        foreach (Route::getRoutes() as $route) {
            $middleware = implode(' ', array_filter($route->gatherMiddleware(), 'is_string'));

            if (preg_match_all('/permission:([a-z_.,]+)/', $middleware, $matches)) {
                foreach ($matches[1] as $group) {
                    foreach (array_filter(explode(',', $group)) as $key) {
                        $used[$key] = true;
                    }
                }
            }
        }

        $unused = array_values(array_diff(PermissionCatalog::keys(), array_keys($used)));

        // Informational. The assertion is that the catalog is non-empty and
        // that at least the core modules are wired to something.
        $this->assertNotEmpty($used, 'No route uses any permission at all.');

        foreach (['users.view', 'settings.view', 'drafts.view'] as $core) {
            $this->assertArrayHasKey($core, $used, "Core permission {$core} gates no route.");
        }

        fwrite(STDERR, "\n[rbac] catalog keys not used on any route: ".count($unused)."\n");
    }
}
