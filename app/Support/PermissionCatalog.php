<?php

namespace App\Support;

/**
 * The permission catalog: every granular capability the backend recognises,
 * grouped by admin-portal module. This is the ALLOW-LIST — RoleController
 * rejects grants for keys not defined here, the seeder syncs it into the
 * `permissions` table, and the role editor renders from it. Adding a key here
 * surfaces it everywhere automatically, which is what keeps new modules from
 * requiring an RBAC rewrite.
 *
 * Key format is `<module>.<action>`. Modules mirror the admin portal nav order
 * so the role editor groups sensibly:
 *
 *   Users & Roles -> Owners -> Listings -> Drafts -> Offers -> Resorts
 *   -> Media -> Reports -> Inbox -> Content -> Settings -> Audit
 *
 * There is no billing module. Listora takes no payment on the website and
 * stores no merchant, gateway, or card data, so there is no processor to
 * credential, no refund to authorise, and no chargeback to work — and
 * therefore no permission that could grant access to any of it.
 *
 * Conventions:
 *   - `view` is the read gate for a module. A role without it should not see
 *     the module in the nav at all.
 *   - `manage` is a coarse catch-all for modules that have no meaningful
 *     split yet; prefer explicit verbs when a module grows.
 *   - Super admins bypass every check (see AppServiceProvider's Gate::before),
 *     so no permission here can lock a super admin out.
 */
final class PermissionCatalog
{
    /** Module key => display label, in admin-portal nav order. */
    public const MODULES = [
        'users' => 'Users',
        'roles' => 'Roles & Permissions',
        'owners' => 'Owners',
        'listings' => 'Listings',
        'drafts' => 'Listing Review Queue',
        'offers' => 'Inquiries & Offers',
        'resorts' => 'Resorts & Clubs',
        'media' => 'Media Library',
        'reports' => 'Reports',
        'inbox' => 'Contact & Support Requests',
        'content' => 'Site Content',
        'settings' => 'Settings',
        'audit' => 'Activity Log',
    ];

    /**
     * Permission key => [module, label, description].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public const PERMISSIONS = [
        // --- Users -----------------------------------------------------
        'users.view' => ['users', 'View Users', 'See the backend user list and individual user profiles.'],
        'users.create' => ['users', 'Create Users', 'Create new backend and customer accounts.'],
        'users.edit' => ['users', 'Edit Users', 'Change a user\'s name, email, and profile details.'],
        'users.deactivate' => ['users', 'Activate / Deactivate Users', 'Disable or restore account access.'],
        'users.reset_password' => ['users', 'Reset Passwords', 'Set a new password on another user\'s account.'],
        'users.assign_roles' => ['users', 'Assign Roles', 'Attach or detach roles on a user. Privileged.'],

        // --- Roles -----------------------------------------------------
        'roles.view' => ['roles', 'View Roles', 'See roles and the permissions attached to each.'],
        'roles.create' => ['roles', 'Create Roles', 'Define new custom roles.'],
        'roles.edit' => ['roles', 'Edit Roles', 'Rename roles and change their granted permissions.'],
        'roles.delete' => ['roles', 'Delete Roles', 'Remove a custom role. System roles can never be deleted.'],

        // --- Owners (the customers who pay to advertise) ---------------
        'owners.view' => ['owners', 'View Owners', 'See owner accounts and the listings they advertise.'],
        'owners.create' => ['owners', 'Create Owners', 'Create owner accounts from the backend.'],
        'owners.edit' => ['owners', 'Edit Owners', 'Update owner profiles and contact details.'],

        // --- Listings --------------------------------------------------
        'listings.view' => ['listings', 'View Listings', 'See listings in the backend, including unpublished ones.'],
        'listings.create' => ['listings', 'Add Listings', 'Create listings directly, bypassing the wizard.'],
        'listings.edit' => ['listings', 'Edit Listings', 'Change descriptions, amenities, location, and asking price.'],
        'listings.publish' => ['listings', 'Publish / Unpublish Listings', 'Control whether a listing is publicly visible.'],
        'listings.assign_plan' => ['listings', 'Assign Advertising Plan', 'Set a listing\'s plan tier, which controls its term length, photo allowance, and placement.'],
        'listings.verify' => ['listings', 'Verify Ownership', 'Record that a listing\'s ownership has been confirmed. Gates publication.'],
        'listings.delete' => ['listings', 'Delete Listings', 'Archive or remove a listing.'],

        // --- Draft review queue ----------------------------------------
        'drafts.view' => ['drafts', 'View Review Queue', 'See listing drafts submitted through the wizard.'],
        'drafts.work' => ['drafts', 'Work the Review Queue', 'Verify ownership, request changes, and decline drafts.'],
        'drafts.publish' => ['drafts', 'Promote Drafts', 'Turn a verified, paid draft into a live listing.'],

        // --- Inquiries & offers ----------------------------------------
        'offers.view' => ['offers', 'View All Inquiries & Offers', 'See every buyer inquiry and offer across the platform, including buyer, owner, amount, IP, and expiry.'],
        'offers.respond' => ['offers', 'Respond to Offers', 'Accept or decline an offer on behalf of a listing owner.'],

        // --- Resorts & clubs -------------------------------------------
        'resorts.view' => ['resorts', 'View Resorts', 'See resorts and vacation clubs.'],
        'resorts.create' => ['resorts', 'Add Resorts', 'Create resorts and vacation clubs.'],
        'resorts.edit' => ['resorts', 'Edit Resorts', 'Update resort details, destinations, and amenities.'],
        'resorts.delete' => ['resorts', 'Delete Resorts', 'Remove a resort.'],

        // --- Media -----------------------------------------------------
        'media.view' => ['media', 'View Media Library', 'Browse uploaded images and documents.'],
        'media.upload' => ['media', 'Upload Images', 'Add new images, documents, and marketing graphics.'],
        'media.edit' => ['media', 'Edit Media', 'Change captions, ordering, and the cover image.'],
        'media.delete' => ['media', 'Delete Media', 'Remove files from the library.'],

        // --- Reports ---------------------------------------------------
        'reports.view' => ['reports', 'View Reports', 'See operational and financial reporting.'],
        'reports.export' => ['reports', 'Export Reports', 'Download report data.'],

        /*
        | Deliberately separate from reports.view.
        |
        | The advertising traffic log carries full visitor IP addresses, and
        | the privacy policy promises they are restricted to administrators
        | for security and fraud investigation. Folding that into "can see
        | reporting" would hand every reporting role a visitor surveillance
        | tool, and the promise would be broken by a role assignment nobody
        | thought of as a privacy decision.
        */
        'advertising.trace' => ['reports', 'Trace Advertising Traffic', 'Search advertising visits and see full visitor IP addresses. Restricted: this is personal data.'],

        // --- Inbox: what the public forms produce ----------------------
        'inbox.view' => ['inbox', 'View Contact & Support Requests', 'Read messages from /contact, support tickets, and job applications.'],
        'inbox.manage' => ['inbox', 'Work Requests', 'Mark requests handled, assign them, and record outcomes.'],

        // --- Site content: what the public pages render ----------------
        'content.view' => ['content', 'View Site Content', 'See careers, press releases, and help articles in the console.'],
        'content.edit' => ['content', 'Edit Site Content', 'Write and update careers, press releases, and help articles.'],
        'content.publish' => ['content', 'Publish Site Content', 'Make content publicly visible, or take it down.'],
        'content.delete' => ['content', 'Delete Site Content', 'Remove a content record entirely.'],

        // --- Settings --------------------------------------------------
        'settings.view' => ['settings', 'View Settings', 'See the settings console.'],
        'settings.edit' => ['settings', 'Edit Settings', 'Change configuration values and feature flags.'],

        // --- Activity log ----------------------------------------------
        //
        // Three separate keys on purpose. Reading what staff changed, reading
        // what visitors did, and taking either out of the system as a file
        // are three different levels of access to personal data, and one
        // permission covering all three would mean granting the least of them
        // grants the most.
        'audit.view' => ['audit', 'View Admin Changes', 'See who changed what across the backend.'],
        'activity.view' => ['audit', 'View Visitor Activity', 'Search the visitor activity log and open session timelines and visitor profiles. Restricted: shows full IP addresses and other personal data.'],
        'activity.export' => ['audit', 'Export Activity Records', 'Download activity and evidentiary records as CSV. Restricted: exported files leave the platform and its retention limits.'],
    ];

    /** @return list<string> Every permission key. */
    public static function keys(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::PERMISSIONS);
    }

    /** @return list<string> Permission keys belonging to one module. */
    public static function forModule(string $module): array
    {
        return array_keys(array_filter(
            self::PERMISSIONS,
            fn (array $meta) => $meta[0] === $module,
        ));
    }

    /**
     * Catalog grouped for the role editor: module key => label => permissions.
     *
     * @return array<string, array{label: string, permissions: array<string, array{label: string, description: string}>}>
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::MODULES as $module => $label) {
            $grouped[$module] = ['label' => $label, 'permissions' => []];
        }

        foreach (self::PERMISSIONS as $key => [$module, $label, $description]) {
            $grouped[$module]['permissions'][$key] = [
                'label' => $label,
                'description' => $description,
            ];
        }

        return $grouped;
    }
}
