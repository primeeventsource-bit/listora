<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Syncs PermissionCatalog into the database and builds the six system roles.
 *
 * Referenced from four places in the codebase before it existed — the catalog
 * docblock, Permission, routes/web.php, and Role::configured() — which is why
 * every granular `permission:` check on the admin console was silently falling
 * back to the binary "is this user an admin" test. The console shipped, the
 * roles editor shipped, and none of it governed anything.
 *
 * Running this flips Role::configured() true, permanently, and granular
 * permissions take over. That is a real cutover, not a no-op: a staff account
 * that was reaching a module through the fallback will stop reaching it unless
 * a role grants it. Super admins are unaffected — they bypass every check.
 *
 * Idempotent. Re-running syncs new catalog keys in, removes retired ones, and
 * leaves custom roles a super admin created completely alone.
 */
class RbacSeeder extends Seeder
{
    /**
     * The six system roles. `key` matches a UserRole enum value, which is how
     * the legacy single-role column on `users` resolves to a permission set —
     * see User::effectiveRoles().
     *
     * `level` gates privilege escalation: a user may never create, edit, or
     * assign a role at or above their own level, so the gaps between these
     * numbers are deliberate room for custom roles to sit between tiers.
     */
    private const SYSTEM_ROLES = [
        UserRole::SuperAdmin->value => [
            'name' => 'Super Admin',
            'description' => 'Unrestricted. Bypasses every permission check, including ones added later.',
            'is_super' => true,
            'level' => 100,
        ],
        UserRole::Admin->value => [
            'name' => 'Admin',
            'description' => 'Runs the console day to day. Cannot mint or assign roles — that stays with a super admin.',
            'is_super' => false,
            'level' => 80,
        ],
        UserRole::ListingSpecialist->value => [
            'name' => 'Listing Specialist',
            'description' => 'Works the review queue and the listings it produces. Staff, but not an admin.',
            'is_super' => false,
            'level' => 40,
        ],
        UserRole::Owner->value => [
            'name' => 'Owner',
            'description' => 'Advertises listings. Holds no console permissions; owner surfaces authorise on ownership.',
            'is_super' => false,
            'level' => 10,
        ],
        UserRole::Traveler->value => [
            'name' => 'Traveler',
            'description' => 'Browses and contacts owners. Holds no console permissions.',
            'is_super' => false,
            'level' => 0,
        ],
    ];

    /**
     * Permissions withheld from Admin and granted only by a super admin.
     *
     * These are the four keys that let a holder rewrite who holds what. An
     * Admin who could edit roles and assign them could grant themselves any
     * permission they lacked, which would make every other restriction on the
     * Admin role decorative. `roles.view` is deliberately NOT here — seeing
     * the role list is how an admin knows who can do what.
     */
    private const SUPER_ADMIN_ONLY = [
        'roles.create',
        'roles.edit',
        'roles.delete',
        'users.assign_roles',
    ];

    /**
     * What a Listing Specialist can reach.
     *
     * Scoped to the Premier plan's "dedicated listing specialist" job: verify
     * ownership, work the queue, fix the listings it produces, and see the
     * inquiries they attract. Deliberately no users, no roles, no settings,
     * and no delete anywhere.
     */
    private const LISTING_SPECIALIST = [
        'drafts.view', 'drafts.work', 'drafts.publish',
        'listings.view', 'listings.edit', 'listings.publish', 'listings.verify', 'listings.assign_plan',
        'owners.view',
        'offers.view',
        'resorts.view', 'resorts.create', 'resorts.edit',
        'media.view', 'media.upload', 'media.edit',
        'inbox.view',
        'content.view',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $permissions = $this->syncPermissions();
            $this->syncRoles($permissions);
        });

        // Role::configured() and every role's permission list are cached, and
        // this seeder has just changed the answer to both.
        Role::bustCache();
    }

    /**
     * @return array<string, int> permission key => id
     */
    private function syncPermissions(): array
    {
        foreach (PermissionCatalog::PERMISSIONS as $key => [$module, $label, $description]) {
            Permission::query()->updateOrCreate(
                ['key' => $key],
                ['module' => $module, 'label' => $label, 'description' => $description],
            );
        }

        // A key removed from the catalog is a capability that no longer
        // exists. Leaving the row would let a role keep granting something
        // nothing checks for any more, which reads as a live grant in the
        // roles editor and is a lie. Cascades clean up permission_role.
        Permission::query()
            ->whereNotIn('key', array_keys(PermissionCatalog::PERMISSIONS))
            ->delete();

        return Permission::query()->pluck('id', 'key')->all();
    }

    /** @param  array<string, int>  $permissions */
    private function syncRoles(array $permissions): void
    {
        foreach (self::SYSTEM_ROLES as $key => $attributes) {
            $role = Role::query()->updateOrCreate(
                ['key' => $key],
                $attributes + ['is_system' => true],
            );

            $role->permissions()->sync(
                $this->permissionIdsFor($key, $permissions)
            );
        }
    }

    /**
     * @param  array<string, int>  $permissions
     * @return list<int>
     */
    private function permissionIdsFor(string $roleKey, array $permissions): array
    {
        $keys = match ($roleKey) {
            // Granted everything explicitly as well as bypassing checks. The
            // bypass is what actually authorises a super admin; the explicit
            // grants are so the roles editor shows the truth rather than an
            // empty list beside the most powerful role in the system.
            UserRole::SuperAdmin->value => array_keys(PermissionCatalog::PERMISSIONS),

            UserRole::Admin->value => array_values(array_diff(
                array_keys(PermissionCatalog::PERMISSIONS),
                self::SUPER_ADMIN_ONLY,
            )),

            UserRole::ListingSpecialist->value => self::LISTING_SPECIALIST,

            // Customers hold no console permissions at all. Owner and traveler
            // surfaces authorise on the data — "is this your listing" — not on
            // a permission key, so granting one here would be inventing a
            // capability nothing checks.
            default => [],
        };

        return array_values(array_intersect_key(
            $permissions,
            array_flip($keys),
        ));
    }
}
