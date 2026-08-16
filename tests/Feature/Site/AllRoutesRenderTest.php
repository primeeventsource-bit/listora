<?php

namespace Tests\Feature\Site;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\ListingDraft;
use App\Models\User;
use Database\Seeders\HelpArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Renders every GET route and fails on a 500.
 *
 * A missing view, a typo'd variable, or a route pointing at a controller
 * method that does not exist all produce a perfectly healthy-looking route
 * list and a fatal error the first time somebody clicks the link. This walks
 * the router itself, so a route added later is covered without anyone
 * remembering to add a test for it.
 */
class AllRoutesRenderTest extends TestCase
{
    use RefreshDatabase;

    /** Routes needing fixtures or side effects this sweep should not perform. */
    private const SKIP = [
        'health',       // pings Redis, absent in CI
        'logout',
        'verification.verify',
    ];

    public function test_every_public_get_route_renders(): void
    {
        $this->seed(HelpArticleSeeder::class);
        $this->assertRoutesRender(user: null);
    }

    public function test_every_owner_route_renders(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'email_verified_at' => now()]);
        Listing::factory()->create(['owner_id' => $owner->id]);

        $this->assertRoutesRender(user: $owner);
    }

    public function test_every_admin_route_renders(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);

        // Give the admin screens something to render, so an empty-state path
        // is not the only one this ever exercises.
        $listing = Listing::factory()->create(['owner_id' => $admin->id]);
        ListingDraft::factory()->create();

        $this->assertRoutesRender(user: $admin, bindings: [
            'listing' => $listing->slug,
            'draft' => ListingDraft::first()->reference,
        ]);
    }

    /**
     * @param  array<string, string>  $bindings  route parameter => value
     */
    private function assertRoutesRender(?User $user, array $bindings = []): void
    {
        $failures = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $name = $route->getName();
            if ($name === null || in_array($name, self::SKIP, true)) {
                continue;
            }

            $uri = $route->uri();

            // Substitute known parameters; skip routes needing one we cannot fill,
            // rather than requesting a URL containing a literal {placeholder}.
            $unresolved = false;
            foreach ($route->parameterNames() as $parameter) {
                if (! array_key_exists($parameter, $bindings)) {
                    $unresolved = true;
                    break;
                }
                $uri = str_replace(['{'.$parameter.'}', '{'.$parameter.'?}'], $bindings[$parameter], $uri);
            }
            if ($unresolved) {
                continue;
            }

            $response = $user
                ? $this->actingAs($user)->get('/'.ltrim($uri, '/'))
                : $this->get('/'.ltrim($uri, '/'));

            // 2xx/3xx are fine, and so is a 403/404 — those are the app making a
            // decision. A 5xx is the app breaking.
            if ($response->getStatusCode() >= 500) {
                $failures[] = sprintf(
                    "%s (%s) -> %d",
                    $name,
                    $uri,
                    $response->getStatusCode(),
                );
            }
        }

        $this->assertSame([], $failures, "Routes returned a server error:\n".implode("\n", $failures));
    }
}
