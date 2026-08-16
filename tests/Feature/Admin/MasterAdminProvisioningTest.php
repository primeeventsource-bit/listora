<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Getting into the console at all.
 *
 * Before this existed the admin portal shipped complete and unreachable: no
 * seeder created an account, no command made one, and RbacSeeder — referenced
 * from four places in the codebase — had never been written, so every granular
 * permission check fell back to a binary "is admin" test.
 *
 * Two things here are worth more than the rest. The provisioned account must
 * be able to actually reach the console, which is not the same as existing;
 * and seeding RBAC must not hand anyone more than their role allows.
 */
class MasterAdminProvisioningTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------- the command

    public function test_it_creates_a_super_admin_that_can_reach_the_console(): void
    {
        $this->artisan('listora:make-admin', [
            '--email' => 'master@listora1.com',
            '--name' => 'Master Admin',
            '--password' => 'ListoraMaster2026',
        ])->assertSuccessful();

        $user = User::sole();

        $this->assertSame(UserRole::SuperAdmin, $user->role);
        $this->assertTrue(Hash::check('ListoraMaster2026', $user->password));

        // The bit that matters, and the bit that was broken: `email_verified_at`
        // is not in User::$fillable, so passing it to create() dropped it
        // silently — and /dashboard sits behind `verified`, so the account
        // existed and still could not get in.
        $this->assertNotNull($user->email_verified_at);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/admin/users')->assertOk();
        $this->actingAs($user)->get('/admin/drafts')->assertOk();

        // /admin/settings deliberately redirects to the first group rather than
        // rendering a chooser, so follow it to the page that actually loads.
        $this->actingAs($user)->get('/admin/settings')
            ->assertRedirect(route('admin.settings.group', 'general'));
        $this->actingAs($user)->get(route('admin.settings.group', 'general'))->assertOk();
    }

    public function test_admin_lands_somewhere_instead_of_404ing(): void
    {
        $this->artisan('listora:make-admin', [
            '--email' => 'master@listora1.com',
            '--name' => 'Master Admin',
            '--password' => 'ListoraMaster2026',
        ]);

        $this->actingAs(User::sole())
            ->get('/admin')
            ->assertRedirect(route('dashboard'));
    }

    public function test_it_promotes_an_existing_account_rather_than_duplicating_it(): void
    {
        $existing = User::factory()->create([
            'email' => 'ops@listora1.com',
            'role' => UserRole::Traveler,
            'email_verified_at' => null,
        ]);

        $this->artisan('listora:make-admin', [
            '--email' => 'ops@listora1.com',
            '--force' => true,
        ])->assertSuccessful();

        $existing->refresh();

        $this->assertSame(1, User::count());
        $this->assertSame(UserRole::SuperAdmin, $existing->role);
        $this->assertNotNull($existing->email_verified_at, 'A promoted admin must clear the verification gate.');
    }

    public function test_the_admin_flag_creates_a_plain_admin_not_a_super_admin(): void
    {
        $this->artisan('listora:make-admin', [
            '--email' => 'admin@listora1.com',
            '--name' => 'Plain Admin',
            '--password' => 'ListoraMaster2026',
            '--admin' => true,
        ])->assertSuccessful();

        $this->assertSame(UserRole::Admin, User::sole()->role);
        $this->assertFalse(User::sole()->isSuperAdmin());
    }

    /** Provisioning must not create the one account exempt from the password policy. */
    public function test_a_weak_password_is_refused(): void
    {
        $this->artisan('listora:make-admin', [
            '--email' => 'master@listora1.com',
            '--name' => 'Master Admin',
            '--password' => 'short',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_a_malformed_email_is_refused(): void
    {
        $this->artisan('listora:make-admin', [
            '--email' => 'not-an-email',
            '--name' => 'Master Admin',
            '--password' => 'ListoraMaster2026',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }

    // ---------------------------------------------------------------- the seeder

    public function test_seeding_activates_granular_permissions(): void
    {
        $this->assertFalse(Role::configured(), 'RBAC should start unseeded.');

        $this->seed(RbacSeeder::class);

        $this->assertTrue(Role::configured());
        $this->assertSame(count(PermissionCatalog::PERMISSIONS), Permission::count());
        $this->assertCount(5, Role::all());
    }

    public function test_it_is_idempotent(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(RbacSeeder::class);

        $this->assertSame(count(PermissionCatalog::PERMISSIONS), Permission::count());
        $this->assertSame(5, Role::count());
    }

    /**
     * An Admin who could edit roles and assign them could grant themselves any
     * permission they lacked, which would make every other limit on the Admin
     * role decorative.
     */
    public function test_an_admin_cannot_rewrite_who_holds_what(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = Role::query()->where('key', UserRole::Admin->value)->sole();
        $granted = $admin->permissions()->pluck('key')->all();

        foreach (['roles.create', 'roles.edit', 'roles.delete', 'users.assign_roles'] as $withheld) {
            $this->assertNotContains($withheld, $granted);
        }

        // But it can still see the role list — knowing who can do what is not
        // the same as being able to change it.
        $this->assertContains('roles.view', $granted);
    }

    public function test_a_listing_specialist_is_staff_without_holding_the_console(): void
    {
        $this->seed(RbacSeeder::class);

        $specialist = User::factory()->create([
            'role' => UserRole::ListingSpecialist,
            'email_verified_at' => now(),
        ]);

        $this->assertTrue($specialist->hasPermission('drafts.work'));
        $this->assertTrue($specialist->hasPermission('listings.verify'));

        foreach (['users.view', 'settings.edit', 'roles.view', 'listings.delete'] as $forbidden) {
            $this->assertFalse($specialist->hasPermission($forbidden), "Specialist should not hold {$forbidden}.");
        }

        $this->actingAs($specialist)->get('/admin/settings')->assertForbidden();
        $this->actingAs($specialist)->get('/admin/users')->assertForbidden();
    }

    /** Customers hold no console permissions; their surfaces authorise on data. */
    public function test_customers_hold_no_console_permissions(): void
    {
        $this->seed(RbacSeeder::class);

        foreach ([UserRole::Owner, UserRole::Traveler] as $role) {
            $roleRow = Role::query()->where('key', $role->value)->sole();

            $this->assertSame(0, $roleRow->permissions()->count(), "{$role->value} should grant nothing.");
        }
    }

    /** Super admins bypass every check, including permissions added later. */
    public function test_a_super_admin_bypasses_checks_for_permissions_that_do_not_exist(): void
    {
        $this->seed(RbacSeeder::class);

        $super = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);

        $this->assertTrue($super->hasPermission('a.permission.invented.tomorrow'));
    }

    /**
     * The nav is built from what a user can reach. A link to a screen that
     * 403s is worse than no link, and until RBAC was seeded there was no way
     * to tell the difference.
     */
    public function test_the_staff_nav_hides_what_the_user_cannot_reach(): void
    {
        $this->seed(RbacSeeder::class);

        $specialist = User::factory()->create([
            'role' => UserRole::ListingSpecialist,
            'email_verified_at' => now(),
        ]);

        $html = $this->actingAs($specialist)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.drafts.index'), $html);
        $this->assertStringNotContainsString(route('admin.settings.index'), $html);
        $this->assertStringNotContainsString(route('admin.users.index'), $html);
    }
}
