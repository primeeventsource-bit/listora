<?php

namespace Tests\Feature\Admin;

use App\Enums\AdEventType;
use App\Enums\UserRole;
use App\Models\AdEvent;
use App\Models\Listing;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The admin activity and visitor audit system.
 *
 * Two things are being pinned here at once, and they pull in opposite
 * directions. The log has to be complete enough to answer "what did this
 * person do, in order, from where" in a dispute - and it holds full IP
 * addresses, so who can open it, and who can take a copy of it out of the
 * system, are separate decisions that must both hold.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);
    }

    private function event(array $attributes = []): AdEvent
    {
        return AdEvent::create(array_merge([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => AdEventType::PageView->value,
            'ip_address' => '203.0.113.44',
            'visitor_id' => 'vis-1111',
            'session_id' => 'sess-aaaa',
            'path' => '/',
            'geo_city' => 'Columbus',
            'geo_region' => 'Ohio',
            'geo_country' => 'US',
            'geo_lat' => 39.9612,
            'geo_lng' => -82.9988,
            'device_category' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'Windows',
            'occurred_at' => now(),
        ], $attributes));
    }

    // -----------------------------------------------------------------
    // Access
    // -----------------------------------------------------------------

    public function test_the_log_is_closed_to_someone_without_the_permission(): void
    {
        $member = User::factory()->create([
            'role' => UserRole::Owner,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($member)->get('/admin/activity')->assertForbidden();
    }

    public function test_an_anonymous_visitor_cannot_open_it(): void
    {
        $this->get('/admin/activity')->assertRedirect();
    }

    // -----------------------------------------------------------------
    // The log itself
    // -----------------------------------------------------------------

    public function test_the_log_shows_activity_with_addresses_and_places(): void
    {
        $this->event();

        $this->actingAs($this->admin())
            ->get('/admin/activity')
            ->assertOk()
            ->assertSee('203.0.113.44')
            ->assertSee('Columbus')
            ->assertSee('Page viewed');
    }

    /** Each filter has to actually exclude, or it is decoration. */
    public function test_every_filter_narrows_the_log(): void
    {
        $listing = Listing::factory()->create();

        $this->event(['ip_address' => '198.51.100.1', 'visitor_id' => 'vis-keep', 'session_id' => 'sess-keep']);
        $this->event([
            'ip_address' => '198.51.100.2',
            'visitor_id' => 'vis-drop',
            'session_id' => 'sess-drop',
            'geo_city' => 'Reykjavik',
            'geo_country' => 'IS',
            'device_category' => 'mobile',
            'event_type' => AdEventType::ListingView->value,
            'listing_id' => $listing->id,
        ]);

        $admin = $this->admin();

        $cases = [
            'ip=198.51.100.1' => ['198.51.100.1', '198.51.100.2'],
            'visitor=vis-keep' => ['198.51.100.1', '198.51.100.2'],
            'session=sess-keep' => ['198.51.100.1', '198.51.100.2'],
            'device=desktop' => ['198.51.100.1', '198.51.100.2'],
            'country=US' => ['198.51.100.1', '198.51.100.2'],
            'city=Columbus' => ['198.51.100.1', '198.51.100.2'],
            'type=page_view' => ['198.51.100.1', '198.51.100.2'],
            'q=Reykjavik' => ['198.51.100.2', '198.51.100.1'],
            'listing='.$listing->id => ['198.51.100.2', '198.51.100.1'],
        ];

        foreach ($cases as $query => [$expected, $excluded]) {
            $this->actingAs($admin)
                ->get('/admin/activity?'.$query)
                ->assertOk()
                ->assertSee($expected)
                ->assertDontSee($excluded);
        }
    }

    // -----------------------------------------------------------------
    // Session timeline
    // -----------------------------------------------------------------

    /**
     * The order is the point. A timeline that renders newest-first tells the
     * story backwards, and the sequence - homepage, listing, send inquiry -
     * is the only reason to open this screen.
     */
    public function test_a_session_timeline_reads_in_the_order_it_happened(): void
    {
        $listing = Listing::factory()->create(['title' => 'Kaanapali Oceanfront']);

        $this->event(['path' => 'first-page', 'occurred_at' => now()->subMinutes(10)]);
        $this->event([
            'path' => 'middle-page',
            'event_type' => AdEventType::ListingView->value,
            'listing_id' => $listing->id,
            'occurred_at' => now()->subMinutes(5),
        ]);
        $this->event([
            'path' => 'last-page',
            'event_type' => AdEventType::InquirySubmitted->value,
            'occurred_at' => now(),
        ]);

        $html = $this->actingAs($this->admin())
            ->get('/admin/activity/session/sess-aaaa')
            ->assertOk()
            ->getContent();

        $this->assertLessThan(
            strpos($html, 'Kaanapali Oceanfront'),
            strpos($html, 'first-page'),
            'The earliest event must render first.',
        );

        $this->assertLessThan(
            strpos($html, 'Inquiry submitted'),
            strpos($html, 'Kaanapali Oceanfront'),
            'The timeline must run forwards.',
        );
    }

    public function test_an_unknown_session_is_a_404_rather_than_an_empty_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/activity/session/nothing-here')
            ->assertNotFound();
    }

    // -----------------------------------------------------------------
    // Visitor profile
    // -----------------------------------------------------------------

    public function test_a_visitor_profile_gathers_every_session(): void
    {
        $this->event(['session_id' => 'sess-1', 'occurred_at' => now()->subDays(2)]);
        $this->event(['session_id' => 'sess-2', 'occurred_at' => now()]);

        $this->actingAs($this->admin())
            ->get('/admin/activity/visitor/vis-1111')
            ->assertOk()
            ->assertSee('vis-1111')
            ->assertSee('203.0.113.44')
            ->assertSee('Columbus');
    }

    /**
     * The association that makes this worth building: activity recorded
     * before somebody signed in, joined to the account they signed into.
     */
    public function test_anonymous_activity_is_joined_to_the_account_that_appears_later(): void
    {
        $user = User::factory()->create(['name' => 'Marisol Vega']);

        $this->event(['occurred_at' => now()->subMinutes(9)]);
        $this->event([
            'event_type' => AdEventType::AccountCreated->value,
            'actor_user_id' => $user->id,
            'occurred_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/activity/visitor/vis-1111')
            ->assertOk()
            ->assertSee('Marisol Vega')
            ->assertSee('Account created');
    }

    // -----------------------------------------------------------------
    // Export
    // -----------------------------------------------------------------

    /**
     * Export is a second permission, not a button on the first. The file
     * leaves the application, and the retention promise in the privacy policy
     * travels no further than the database.
     */
    public function test_export_needs_its_own_permission(): void
    {
        $this->event();

        // A role that can read the log and cannot take a copy of it away.
        $role = Role::create([
            'key' => 'activity-reader',
            'name' => 'Activity reader',
            'level' => 10,
        ]);

        // Created here rather than assumed: the permissions table is seeded by
        // RbacSeeder, which these tests do not run, and syncing an empty set
        // grants nothing while looking like it granted something.
        $view = Permission::firstOrCreate(
            ['key' => 'activity.view'],
            ['module' => 'audit', 'label' => 'View Visitor Activity', 'description' => 'Test fixture.'],
        );

        Permission::firstOrCreate(
            ['key' => 'activity.export'],
            ['module' => 'audit', 'label' => 'Export Activity Records', 'description' => 'Test fixture.'],
        );

        $role->permissions()->sync([$view->id]);

        $reader = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $reader->roles()->sync([$role->id]);

        $this->actingAs($reader)->get('/admin/activity')->assertOk();
        $this->actingAs($reader)->get('/admin/activity/export')->assertForbidden();
    }

    public function test_the_export_carries_the_evidentiary_columns(): void
    {
        $this->event(['event_type' => AdEventType::AgreementAccepted->value]);

        $response = $this->actingAs($this->admin())
            ->get('/admin/activity/export')
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Occurred at (UTC)', $csv);
        $this->assertStringContainsString('IP address', $csv);
        $this->assertStringContainsString('Agreement accepted', $csv);
        $this->assertStringContainsString('203.0.113.44', $csv);
    }
}
