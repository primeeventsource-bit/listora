<?php

use App\Http\Controllers\Admin\DraftController as AdminDraftController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\FeatureFlagController;
use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\ListingController as AdminListingController;
use App\Http\Controllers\Admin\OfferController as AdminOfferController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CareersController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingWizardController;
use App\Http\Controllers\NewsletterSubscriptionController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\Owner\ListingController as OwnerListingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PressController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Public marketing + browse surfaces. Anonymous-friendly throughout: a
// traveler should never need an account to find a listing or contact its
// owner, which is the whole proposition.
// ---------------------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/browse', [ListingController::class, 'index'])->name('listings.index');
Route::get('/listing/{listing}', [ListingController::class, 'show'])->name('listings.show');

// Free-text message to an owner. Throttled because it sends mail on behalf of
// an anonymous visitor.
Route::post('/listing/{listing}/inquire', [InquiryController::class, 'store'])
    ->middleware('throttle:10,1')->name('inquiries.store');

// A priced offer, as distinct from an inquiry. Expires on a fixed clock.
Route::post('/listing/{listing}/offer', [OfferController::class, 'store'])
    ->middleware('throttle:10,1')->name('offers.store');

// The "list your property" wizard. Public — an owner should not need an
// account to ask about advertising.
Route::get('/list-your-property', [ListingWizardController::class, 'create'])->name('list.create');
Route::post('/list-your-property', [ListingWizardController::class, 'store'])
    ->middleware('throttle:10,1')->name('list.store');

Route::get('/how-it-works', [PageController::class, 'how'])->name('how');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/apps', [PageController::class, 'apps'])->name('apps');
Route::get('/about', [PageController::class, 'about'])->name('about');

// The Contact tab became Help. /contact is kept as a redirect rather than
// deleted: it was linked from the old nav and footer and may be bookmarked or
// indexed, and a 404 on the page people reach for when they need help is the
// worst possible dead end. The POST target stays put so the form on the Help
// page has somewhere to submit.
Route::permanentRedirect('/contact', '/help#ask')->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:10,1')->name('contact.store');

// Newsletter signup — distinct from /register (account creation).
Route::get('/signup', [NewsletterSubscriptionController::class, 'show'])->name('signup.show');
Route::post('/signup', [NewsletterSubscriptionController::class, 'store'])
    ->middleware('throttle:10,1')->name('signup.store');

// Literal /careers routes must precede the {opening} wildcard.
Route::get('/careers', [CareersController::class, 'index'])->name('careers.index');
Route::get('/careers/{opening}', [CareersController::class, 'show'])->name('careers.show');
Route::post('/careers/{opening}/apply', [CareersController::class, 'apply'])
    ->middleware('throttle:6,1')->name('careers.apply');

Route::get('/press', [PressController::class, 'index'])->name('press.index');
Route::get('/press/{release}', [PressController::class, 'show'])->name('press.show');

// ---------------------------------------------------------------------------
// Help center. Public. /help/search is JSON and is used both by the help
// page's live search and by the support chat agent's search tool, so the
// literal route must precede the {slug} wildcard.
// ---------------------------------------------------------------------------
Route::get('/help', [HelpController::class, 'index'])->name('help.index');
Route::get('/help/search', [HelpController::class, 'search'])->name('help.search');
Route::get('/help/{slug}', [HelpController::class, 'show'])->name('help.show');

// ---------------------------------------------------------------------------
// Legal docs. The /legal/versions JSON endpoint exposes which version+hash is
// currently in force per kind, so auditors and the chat agent can reconcile
// against terms_versions.
// ---------------------------------------------------------------------------
Route::get('/legal/tos', [LegalController::class, 'tos'])->name('legal.tos');

// Friendly aliases for the nav and for anything a customer might type or be
// given verbally. 301s rather than second routes rendering the same view:
// /legal/tos stays the single canonical URL, which matters because it is the
// address recorded on every terms_acceptance row.
Route::permanentRedirect('/terms', '/legal/tos')->name('terms');
Route::permanentRedirect('/terms-and-conditions', '/legal/tos');
Route::permanentRedirect('/privacy', '/legal/privacy');
Route::get('/legal/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/legal/advertising-agreement', [LegalController::class, 'advertisingAgreement'])->name('legal.advertising-agreement');
Route::get('/legal/versions', [LegalController::class, 'versions'])->name('legal.versions');

Route::middleware('auth')->group(function () {
    Route::get('/legal/review-and-accept', [LegalController::class, 'reviewAndAccept'])->name('legal.review-and-accept');
    Route::post('/legal/review-and-accept', [LegalController::class, 'acceptCurrent'])->name('legal.review-and-accept.submit');
});

// Deeper health check than Laravel's built-in /up. Pings DB and Redis and
// returns 503 if either is down; mail is reported but advisory only.
Route::get('/health', HealthController::class)->name('health');

Route::get('/dashboard', [DashboardController::class, 'show'])
    ->middleware(['auth', 'verified', 'terms.current'])
    ->name('dashboard');

// ---------------------------------------------------------------------------
// Owner surfaces. An owner manages the listings they advertise and responds to
// what those listings attract. Everything here scopes through
// `listings.owner_id` — never through a denormalised column — so a listing
// that changes hands does not stay readable by its previous owner.
// ---------------------------------------------------------------------------
Route::middleware(['auth', 'terms.current'])
    ->prefix('account')
    ->name('owner.')
    ->group(function () {
        Route::get('listings', [OwnerListingController::class, 'index'])->name('listings.index');
        Route::get('listings/{listing}/edit', [OwnerListingController::class, 'edit'])->name('listings.edit');
        Route::patch('listings/{listing}', [OwnerListingController::class, 'update'])->name('listings.update');
        Route::post('listings/{listing}/pause', [OwnerListingController::class, 'pause'])->name('listings.pause');
        Route::post('listings/{listing}/resume', [OwnerListingController::class, 'resume'])->name('listings.resume');

        Route::get('inquiries', [OwnerListingController::class, 'inquiries'])->name('inquiries.index');

        Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
        Route::post('offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
        Route::post('offers/{offer}/decline', [OfferController::class, 'decline'])->name('offers.decline');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ---------------------------------------------------------------------------
// Admin portal. Gated per action on granular RBAC permissions
// (App\Support\PermissionCatalog) rather than on a single binary admin flag,
// so a custom role can be given exactly one module.
//
// EVERY route in this group MUST carry a `permission:` middleware — the outer
// group only proves authentication. A route added without one is reachable by
// any signed-in user. RbacPermissionCoverageTest fails the build if that
// happens.
//
// While RBAC is unseeded on an environment, `permission:` falls back to the
// legacy "is this user an admin" check, so this group behaves exactly as it
// did before until RbacSeeder runs. See App\Models\Role::configured().
// ---------------------------------------------------------------------------
Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin user management. Every state-changing action writes to
        // admin_audit_logs via AdminAuditLogService.
        Route::get('users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
        Route::post('users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('permission:users.view')->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.edit')->name('users.edit');
        Route::patch('users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit')->name('users.update');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:users.deactivate')->name('users.deactivate');
        Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])->middleware('permission:users.deactivate')->name('users.reactivate');
        Route::get('users/{user}/login-history', [UserController::class, 'loginHistory'])->middleware('permission:users.view')->name('users.login-history');

        // Roles & Permissions. Creating roles is deliberately narrower than
        // creating users — see PermissionCatalog and RbacSeeder.
        Route::get('roles', [RoleController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
        Route::get('roles/create', [RoleController::class, 'create'])->middleware('permission:roles.create')->name('roles.create');
        Route::post('roles', [RoleController::class, 'store'])->middleware('permission:roles.create')->name('roles.store');
        Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:roles.edit')->name('roles.edit');
        Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit')->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete')->name('roles.destroy');

        // The listing review queue: what the public wizard produces. Ownership
        // verification lives here, and it is what gates publication.
        Route::get('drafts', [AdminDraftController::class, 'index'])->middleware('permission:drafts.view')->name('drafts.index');
        Route::get('drafts/{draft}', [AdminDraftController::class, 'show'])->middleware('permission:drafts.view')->name('drafts.show');
        Route::post('drafts/{draft}/verify', [AdminDraftController::class, 'verify'])->middleware('permission:drafts.work')->name('drafts.verify');
        Route::post('drafts/{draft}/decline', [AdminDraftController::class, 'decline'])->middleware('permission:drafts.work')->name('drafts.decline');
        Route::post('drafts/{draft}/publish', [AdminDraftController::class, 'publish'])->middleware('permission:drafts.publish')->name('drafts.publish');

        // Listings, once they exist.
        Route::get('listings', [AdminListingController::class, 'index'])->middleware('permission:listings.view')->name('listings.index');
        Route::get('listings/{listing}/edit', [AdminListingController::class, 'edit'])->middleware('permission:listings.edit')->name('listings.edit');
        Route::patch('listings/{listing}', [AdminListingController::class, 'update'])->middleware('permission:listings.edit')->name('listings.update');
        Route::post('listings/{listing}/publish', [AdminListingController::class, 'publish'])->middleware('permission:listings.publish')->name('listings.publish');
        Route::post('listings/{listing}/unpublish', [AdminListingController::class, 'unpublish'])->middleware('permission:listings.publish')->name('listings.unpublish');
        Route::post('listings/{listing}/plan', [AdminListingController::class, 'assignPlan'])->middleware('permission:listings.assign_plan')->name('listings.plan');

        // Cross-platform inquiry/offer register. Read-only: responding is the
        // listing owner's action and lives on the owner dashboard.
        Route::get('offers', [AdminOfferController::class, 'index'])->middleware('permission:offers.view')->name('offers.index');

        // Everything the public forms produce. Without this the /contact form
        // and support tickets would be write-only.
        Route::get('inbox', [InboxController::class, 'index'])->middleware('permission:inbox.view')->name('inbox.index');
        Route::post('inbox/contact/{message}/handled', [InboxController::class, 'markHandled'])
            ->middleware('permission:inbox.manage')->name('inbox.handled');

        // -------------------------------------------------------------------
        // Settings & Configuration console. Panes render from SettingsSchema;
        // every write is validated against the allow-list, audited, and busts
        // the settings cache. Literal routes MUST precede the settings/{group}
        // wildcard.
        // -------------------------------------------------------------------
        Route::get('settings', [SettingsController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');

        Route::get('settings/flags', [FeatureFlagController::class, 'index'])->middleware('permission:settings.view')->name('settings.flags');
        Route::put('settings/flags/{key}', [FeatureFlagController::class, 'update'])->middleware('permission:settings.edit')->name('settings.flags.update');

        Route::get('settings/templates', [EmailTemplateController::class, 'index'])->middleware('permission:settings.view')->name('settings.templates');
        Route::post('settings/templates', [EmailTemplateController::class, 'store'])->middleware('permission:settings.edit')->name('settings.templates.store');

        Route::get('settings/{group}', [SettingsController::class, 'showGroup'])->middleware('permission:settings.view')->name('settings.group');
        Route::put('settings/{group}', [SettingsController::class, 'updateGroup'])->middleware('permission:settings.edit')->name('settings.group.update');
    });

require __DIR__.'/auth.php';
