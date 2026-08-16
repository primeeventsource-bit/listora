<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Database\Seeders\TestAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * The per-role sign-in fixtures.
 *
 * A fixture that exists but cannot sign in is worse than none — it looks like
 * the role is broken when the account is. So these check the whole path: the
 * credentials work, the session lands somewhere, and it lands on the surface
 * that role is supposed to see.
 *
 * The production guard is tested too, because these accounts share a known
 * password and one of them is a super admin.
 */
class TestAccountsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'ListoraTest2026';

    private function seedAccounts(): void
    {
        $this->seed(RbacSeeder::class);
        $this->seed(TestAccountsSeeder::class);
    }

    public function test_every_role_gets_an_account_that_can_sign_in(): void
    {
        $this->seedAccounts();

        $expected = [
            'super@listora1.test' => UserRole::SuperAdmin,
            'admin@listora1.test' => UserRole::Admin,
            'specialist@listora1.test' => UserRole::ListingSpecialist,
            'owner@listora1.test' => UserRole::Owner,
            'traveler@listora1.test' => UserRole::Traveler,
        ];

        foreach ($expected as $email => $role) {
            $this->post('/login', ['email' => $email, 'password' => self::PASSWORD])
                ->assertRedirect();

            $this->assertAuthenticated();
            $this->assertSame($role, auth()->user()->role, "{$email} should be {$role->value}.");

            // Every fixture must clear the `verified` gate on /dashboard.
            $this->get('/dashboard')->assertOk();

            $this->post('/logout');
        }
    }

    public function test_each_account_lands_on_the_dashboard_for_its_role(): void
    {
        $this->seedAccounts();

        $cases = [
            'super@listora1.test' => 'Review queue',
            'admin@listora1.test' => 'Review queue',
            'specialist@listora1.test' => 'Review queue',
        ];

        foreach ($cases as $email => $expected) {
            $user = User::query()->where('email', $email)->sole();

            $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee($expected);
        }

        // The owner view is chosen by asking the data, not the role column, so
        // the owner fixture only reaches it because the seeder gave it
        // listings. Without that it silently falls through to the traveler view.
        $owner = User::query()->where('email', 'owner@listora1.test')->sole();
        $this->assertGreaterThan(0, Listing::query()->ownedBy($owner->id)->count());
        $this->actingAs($owner)->get('/dashboard')->assertOk()->assertSee('My listings');

        $traveler = User::query()->where('email', 'traveler@listora1.test')->sole();
        $this->actingAs($traveler)->get('/dashboard')->assertOk();
    }

    public function test_the_console_is_reachable_by_staff_and_closed_to_customers(): void
    {
        $this->seedAccounts();

        foreach (['super@listora1.test', 'admin@listora1.test'] as $email) {
            $this->actingAs(User::query()->where('email', $email)->sole())
                ->get('/admin/users')->assertOk();
        }

        // Staff, but deliberately not an admin.
        $this->actingAs(User::query()->where('email', 'specialist@listora1.test')->sole())
            ->get('/admin/drafts')->assertOk();
        $this->actingAs(User::query()->where('email', 'specialist@listora1.test')->sole())
            ->get('/admin/users')->assertForbidden();

        foreach (['owner@listora1.test', 'traveler@listora1.test'] as $email) {
            $this->actingAs(User::query()->where('email', $email)->sole())
                ->get('/admin/drafts')->assertForbidden();
        }
    }

    public function test_it_is_idempotent(): void
    {
        $this->seedAccounts();
        $this->seed(TestAccountsSeeder::class);

        $this->assertSame(5, User::count());

        $this->post('/login', ['email' => 'admin@listora1.test', 'password' => self::PASSWORD]);
        $this->assertAuthenticated();
    }

    /**
     * A shared, known password on a live super admin is not a test fixture.
     * `--force` is not accepted as consent here either — that flag exists so
     * migrations can run unattended, and letting it also mean this would make
     * one careless deploy command sufficient.
     */
    public function test_it_refuses_to_run_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('refuses to run in production');

        // Invoked directly rather than through $this->seed(): the db:seed
        // command puts its own production confirmation prompt in front, which
        // would mean this never reached the guard being tested.
        (new TestAccountsSeeder)->run();
    }

    public function test_no_accounts_are_created_when_it_refuses(): void
    {
        app()->detectEnvironment(fn () => 'production');

        try {
            (new TestAccountsSeeder)->run();
        } catch (RuntimeException) {
            // expected
        }

        $this->assertSame(0, User::count());
    }
}
