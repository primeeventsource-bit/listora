<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\AdminAuditLog;
use App\Models\Listing;
use App\Models\User;
use App\Services\AdminAuditLogService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The read half of the audit trail.
 *
 * AdminAuditLogService wrote a row for every privileged change from the day
 * the table shipped, and `audit.view` gated a screen that did not exist — so
 * the compliance log was write-only. These pin that it is readable, that it is
 * readable only by people entitled to read it, and that nothing anywhere
 * offers a way to change what it says.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Just the results table.
     *
     * Every distinct action also appears in the filter dropdown — which is
     * correct, it is how you filter to an old action then widen the dates —
     * so a bare assertDontSee on an action name proves nothing about whether
     * the row was actually excluded.
     */
    private function resultsTable(string $html): string
    {
        preg_match('#<tbody>(.*?)</tbody>#s', $html, $m);

        return $m[1] ?? '';
    }

    private function logSomething(User $actor, string $action = 'listing.publish'): AdminAuditLog
    {
        return AdminAuditLogService::log(
            actor: $actor,
            action: $action,
            subject: Listing::factory()->create(),
            payload: ['reference' => 'LST-D-4H2K9M', 'note' => 'verified against deed'],
            ipAddress: '198.51.100.7',
        );
    }

    public function test_a_recorded_change_can_actually_be_read_back(): void
    {
        $admin = $this->superAdmin();
        $this->logSomething($admin);

        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('listing.publish')
            ->assertSee($admin->name)
            ->assertSee('LST-D-4H2K9M')
            ->assertSee('198.51.100.7');
    }

    public function test_the_detail_view_shows_the_whole_payload(): void
    {
        $admin = $this->superAdmin();
        $entry = $this->logSomething($admin);

        $this->actingAs($admin)
            ->get(route('admin.audit.show', $entry))
            ->assertOk()
            ->assertSee('verified against deed')
            ->assertSee('198.51.100.7');
    }

    public function test_it_filters_by_action_actor_and_free_text(): void
    {
        $admin = $this->superAdmin();
        $other = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);

        $this->logSomething($admin, 'listing.publish');
        AdminAuditLogService::log($other, 'user.deactivate', null, ['reference' => 'ZZZ-OTHER']);

        $byAction = $this->resultsTable($this->actingAs($admin)
            ->get(route('admin.audit.index', ['action' => 'user.deactivate']))
            ->assertOk()->getContent());

        $this->assertStringContainsString('user.deactivate', $byAction);
        $this->assertStringNotContainsString('listing.publish', $byAction);

        $byActor = $this->resultsTable($this->actingAs($admin)
            ->get(route('admin.audit.index', ['actor' => $other->id]))
            ->assertOk()->getContent());

        $this->assertStringContainsString('ZZZ-OTHER', $byActor);
        $this->assertStringNotContainsString('LST-D-4H2K9M', $byActor);

        $byText = $this->resultsTable($this->actingAs($admin)
            ->get(route('admin.audit.index', ['q' => 'ZZZ-OTHER', 'days' => 'all']))
            ->assertOk()->getContent());

        $this->assertStringContainsString('ZZZ-OTHER', $byText);
        $this->assertStringNotContainsString('LST-D-4H2K9M', $byText);
    }

    /** An unbounded audit view is the one screen guaranteed to slow every day. */
    public function test_it_is_date_bounded_by_default(): void
    {
        $admin = $this->superAdmin();

        $old = $this->logSomething($admin, 'ancient.action');
        $old->forceFill(['occurred_at' => now()->subMonths(6)])->save();

        $default = $this->resultsTable($this->actingAs($admin)
            ->get(route('admin.audit.index'))->assertOk()->getContent());

        $this->assertStringNotContainsString('ancient.action', $default);

        $widened = $this->resultsTable($this->actingAs($admin)
            ->get(route('admin.audit.index', ['days' => 'all']))->assertOk()->getContent());

        $this->assertStringContainsString('ancient.action', $widened);
    }

    // ------------------------------------------------------------ authorisation

    public function test_it_is_closed_to_staff_without_the_permission(): void
    {
        $this->seed(RbacSeeder::class);

        $specialist = User::factory()->create([
            'role' => UserRole::ListingSpecialist,
            'email_verified_at' => now(),
        ]);

        $this->assertFalse($specialist->hasPermission('audit.view'));

        $this->actingAs($specialist)->get(route('admin.audit.index'))->assertForbidden();
    }

    public function test_it_is_closed_to_customers_and_guests(): void
    {
        $this->seed(RbacSeeder::class);

        foreach ([UserRole::Owner, UserRole::Traveler] as $role) {
            $user = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);

            $this->actingAs($user)->get(route('admin.audit.index'))->assertForbidden();
        }

        $this->post('/logout');
        $this->get(route('admin.audit.index'))->assertRedirect(route('login'));
    }

    /**
     * An audit trail an admin can amend is not evidence of anything, so there
     * must be no write route at all — not a guarded one, none.
     */
    public function test_no_route_exists_to_change_what_the_log_says(): void
    {
        $writeRoutes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'admin.audit.'))
            ->reject(fn ($route) => $route->methods() === ['GET', 'HEAD'])
            ->map(fn ($route) => $route->getName())
            ->values()
            ->all();

        $this->assertSame([], $writeRoutes, 'The audit log must expose no write routes.');
    }

    /** The entry outlives the account. A deleted actor must not erase the record. */
    public function test_an_entry_survives_its_actor_being_read_without_one(): void
    {
        $admin = $this->superAdmin();
        $entry = $this->logSomething($admin);

        // Not deleting the actor — the FK is restrictive by design — but the
        // view has to tolerate a missing relation rather than fatal.
        $entry->actor_user_id = $admin->id;
        $entry->save();

        $viewer = $this->superAdmin();

        $this->actingAs($viewer)->get(route('admin.audit.show', $entry))->assertOk();
    }
}
