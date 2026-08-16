<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * One signable account per role, for testing the console and the two customer
 * dashboards without hand-making users.
 *
 * These accounts share a known password, which is exactly the thing that must
 * never reach production — a known credential on a live super admin is not a
 * test fixture, it is a way in. So this refuses to run there, loudly, rather
 * than trusting whoever typed `db:seed` to have meant it. It is deliberately
 * absent from DatabaseSeeder for the same reason.
 *
 *   php artisan db:seed --class=TestAccountsSeeder
 *
 * Override the shared password with LISTORA_TEST_PASSWORD. It still has to
 * clear `users.min_password_length`, because an account that cannot be created
 * through the app's own policy is not testing the app.
 *
 * Idempotent: re-running resets each account back to a known state, which is
 * the point of a fixture.
 */
class TestAccountsSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'ListoraTest2026';

    /** email => [role, display name] */
    private const ACCOUNTS = [
        'super@listora1.test' => [UserRole::SuperAdmin, 'Sam Super'],
        'admin@listora1.test' => [UserRole::Admin, 'Alex Admin'],
        'specialist@listora1.test' => [UserRole::ListingSpecialist, 'Sasha Specialist'],
        'owner@listora1.test' => [UserRole::Owner, 'Olivia Owner'],
        'traveler@listora1.test' => [UserRole::Traveler, 'Theo Traveler'],
    ];

    public function run(): void
    {
        $this->refuseInProduction();

        $password = (string) (env('LISTORA_TEST_PASSWORD') ?: self::DEFAULT_PASSWORD);
        $this->assertPasswordMeetsPolicy($password);

        $rows = [];

        foreach (self::ACCOUNTS as $email => [$role, $name]) {
            $user = $this->upsert($email, $role, $name, $password);
            $rows[] = [$role->label(), $email, $password];
        }

        $this->giveTheOwnerSomethingToLookAt();

        $this->command?->newLine();
        $this->command?->table(['Role', 'Email', 'Password'], $rows);
        $this->command?->warn('Test accounts share a known password. Never seed these anywhere public.');
    }

    /**
     * The whole guard. `--force` deliberately does not open a door here: the
     * flag exists so migrations can run unattended in production, and reusing
     * it to also mean "yes, put a known super-admin password on the live site"
     * would make one careless deploy command sufficient.
     */
    private function refuseInProduction(): void
    {
        if (App::environment('production')) {
            throw new RuntimeException(
                'TestAccountsSeeder refuses to run in production: it creates accounts with a shared, '
                .'known password including a super admin. Use `php artisan listora:make-admin` instead.'
            );
        }
    }

    private function assertPasswordMeetsPolicy(string $password): void
    {
        $min = (int) setting('users.min_password_length', 12);

        if (mb_strlen($password) < $min) {
            throw new RuntimeException(
                "LISTORA_TEST_PASSWORD is shorter than the {$min}-character minimum this app enforces."
            );
        }
    }

    private function upsert(string $email, UserRole $role, string $name, string $password): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->fill([
            'name' => $name,
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        // Property assignment, not fill(): `email_verified_at` is absent from
        // User::$fillable, and /dashboard sits behind `verified` — a fixture
        // that cannot sign in is not a fixture.
        $user->email_verified_at = now();
        $user->deactivated_at = null;
        $user->save();

        $this->attachRbacRole($user, $role);

        return $user;
    }

    /**
     * The primary `role` column already resolves to a permission set, so this
     * is belt and braces — but it makes each fixture show up in the roles
     * console as holding its role rather than appearing to hold nothing.
     */
    private function attachRbacRole(User $user, UserRole $role): void
    {
        if (! Role::configured()) {
            return;
        }

        $roleRow = Role::query()->where('key', $role->value)->first();

        if ($roleRow) {
            $user->roles()->syncWithoutDetaching([$roleRow->id => ['assigned_at' => now()]]);
            $user->forgetEffectiveRoles();
        }
    }

    /**
     * DashboardController picks the owner view by asking the data — "does this
     * user own a listing" — not the role column. Without listings the owner
     * fixture lands on the traveler dashboard, which makes the owner role look
     * broken when it is working exactly as designed.
     */
    private function giveTheOwnerSomethingToLookAt(): void
    {
        $owner = User::query()->where('email', 'owner@listora1.test')->first();

        if (! $owner) {
            return;
        }

        if (Listing::query()->ownedBy($owner->id)->exists()) {
            return;
        }

        $unclaimed = Listing::query()->whereNull('owner_id')->limit(3)->get();

        foreach ($unclaimed as $listing) {
            $listing->update(['owner_id' => $owner->id]);
        }

        // Nothing to claim means ListoraSeeder has not run — a fresh database,
        // or a test that seeds only this. Make one rather than leaving the
        // owner fixture on the traveler dashboard, which is the confusing
        // outcome this method exists to prevent.
        if ($unclaimed->isEmpty()) {
            Listing::factory()->count(2)->create([
                'owner_id' => $owner->id,
                'status' => ListingStatus::Active->value,
                'published_at' => now(),
            ]);
        }
    }
}
