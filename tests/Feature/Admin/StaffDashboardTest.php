<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The operations console home.
 *
 * It used to render a fixed five tiles for anyone who passed isStaff(), so a
 * role without `listings.view` was shown the live listing count and handed a
 * link to its own 403 — it leaked the number and then refused the page. These
 * pin that a tile appears only when its destination is reachable.
 */
class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function staff(UserRole $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'email_verified_at' => now(),
        ]);
    }

    public function test_a_super_admin_sees_every_module(): void
    {
        $this->seed(RbacSeeder::class);

        $html = $this->actingAs($this->staff(UserRole::SuperAdmin))
            ->get('/dashboard')->assertOk()->getContent();

        foreach ([
            'Awaiting verification', 'Live listings', 'Open offers',
            'Questions unanswered', 'Accounts', 'Logged changes (7d)',
        ] as $tile) {
            $this->assertStringContainsString($tile, $html);
        }
    }

    /**
     * The case the rebuild existed for. A specialist can work the queue and the
     * listings it produces, and holds neither users.view nor audit.view.
     */
    public function test_a_specialist_is_not_shown_counts_they_cannot_open(): void
    {
        $this->seed(RbacSeeder::class);

        $specialist = $this->staff(UserRole::ListingSpecialist);
        $html = $this->actingAs($specialist)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Awaiting verification', $html);
        $this->assertStringContainsString('Live listings', $html);

        $this->assertStringNotContainsString('Accounts', $html);
        $this->assertStringNotContainsString('Logged changes', $html);

        // And the links behind the hidden tiles are absent, not merely unlabelled.
        $this->assertStringNotContainsString(route('admin.users.index'), $html);
        $this->assertStringNotContainsString(route('admin.audit.index'), $html);
    }

    /** Every tile rendered must lead somewhere the viewer can actually open. */
    public function test_every_tile_shown_leads_to_a_page_the_viewer_can_open(): void
    {
        $this->seed(RbacSeeder::class);

        $specialist = $this->staff(UserRole::ListingSpecialist);
        $html = $this->actingAs($specialist)->get('/dashboard')->assertOk()->getContent();

        preg_match_all('#<a href="([^"]*/admin/[^"]*)" class="stat#', $html, $m);

        $this->assertNotEmpty($m[1], 'The specialist should see at least one tile.');

        foreach (array_unique($m[1]) as $url) {
            $this->actingAs($specialist)
                ->get($url)
                ->assertSuccessful();
        }
    }

    /**
     * Staff with no module permissions at all get an explanation rather than a
     * blank page that looks broken.
     */
    public function test_staff_holding_nothing_are_told_so(): void
    {
        $this->seed(RbacSeeder::class);

        $stripped = $this->staff(UserRole::ListingSpecialist);

        // Strip the role's grants to simulate a custom staff role with none.
        Role::query()->where('key', UserRole::ListingSpecialist->value)
            ->sole()->permissions()->detach();
        Role::bustCache();

        $this->actingAs($stripped)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Your account is staff, but holds no module permissions');
    }

    public function test_the_review_queue_is_hidden_from_staff_who_cannot_work_it(): void
    {
        $this->seed(RbacSeeder::class);

        Role::query()->where('key', UserRole::ListingSpecialist->value)
            ->sole()->permissions()->detach();
        Role::bustCache();

        $this->actingAs($this->staff(UserRole::ListingSpecialist))
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Review queue');
    }

    /** Customers never reach the staff dashboard, whatever their data looks like. */
    public function test_customers_get_their_own_dashboard(): void
    {
        $this->seed(RbacSeeder::class);

        $owner = $this->staff(UserRole::Owner);
        Listing::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertDontSee('What needs you today');

        $this->actingAs($this->staff(UserRole::Traveler))
            ->get('/dashboard')->assertOk()->assertDontSee('What needs you today');
    }
}
