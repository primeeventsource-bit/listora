<?php

namespace Tests\Feature\Admin;

use App\Enums\AdEventType;
use App\Enums\Surface;
use App\Enums\UserRole;
use App\Models\AdEvent;
use App\Models\PpcVisitor;
use App\Models\TrackingEvent;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Traffic, geography, and paid attribution.
 *
 * `reports.view` and `reports.export` gated a module that did not exist, while
 * TrackingService wrote a geo-enriched row for every event and
 * CaptureLandingAttribution wrote a first-touch row for every paid click. Both
 * were collected and neither could be read.
 *
 * The map is the part most able to be quietly wrong: a pin at the wrong
 * coordinate still renders, and nothing complains.
 */
class ReportsTest extends TestCase
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
     * Just the map's own SVG.
     *
     * Counting `<circle` across the whole document also catches the icons in
     * the site chrome, which is how the first version of these tests
     * "found" two pins on a page with no located traffic at all.
     */
    private function mapSvg(string $html): string
    {
        preg_match('#<svg[^>]*aria-label="Visitor locations[^>]*>(.*?)</svg>#s', $html, $m);

        return $m[1] ?? '';
    }

    /**
     * A visit, recorded in both places the reports page now reads.
     *
     * tracking_events carries the attribution and surface panels; the
     * geography panels moved to ad_events, because tracking_events only ever
     * holds visits that arrived with attribution parameters and the map was
     * therefore empty on a site not yet running campaigns.
     *
     * Written to both from one helper so a test still describes one visit,
     * rather than every case having to know which table each panel reads.
     */
    private function event(array $geo, string $type = 'page_view', ?string $visitor = null, int $daysAgo = 1): TrackingEvent
    {
        $visitorId = $visitor ?? 'visitor-'.uniqid();
        $occurredAt = now()->subDays($daysAgo);

        AdEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => AdEventType::ListingView->value,
            'visitor_id' => $visitorId,
            'ip_address' => '198.51.100.7',
            'geo_city' => $geo['city'] ?? null,
            'geo_country' => $geo['country'] ?? null,
            'geo_lat' => $geo['latitude'] ?? null,
            'geo_lng' => $geo['longitude'] ?? null,
            'occurred_at' => $occurredAt,
        ]);

        return TrackingEvent::create([
            'event_type' => $type,
            'visitor_id' => $visitorId,
            'surface' => Surface::Web->value,
            'ip_address' => '198.51.100.7',
            'metadata' => ['geo' => $geo],
            'occurred_at' => $occurredAt,
        ]);
    }

    public function test_it_reports_traffic_geography_and_clicks(): void
    {
        $this->event(['country' => 'US', 'city' => 'Austin', 'latitude' => 30.26, 'longitude' => -97.74], 'page_view', 'v1');
        $this->event(['country' => 'US', 'city' => 'Austin', 'latitude' => 30.26, 'longitude' => -97.74], 'listing_view', 'v1');
        $this->event(['country' => 'GB', 'city' => 'London', 'latitude' => 51.5, 'longitude' => -0.12], 'inquiry_submitted', 'v2');

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))->assertOk()->getContent();

        // Three events, two visitors, two countries.
        $this->assertStringContainsString('Austin', $html);
        $this->assertStringContainsString('London', $html);
        $this->assertStringContainsString('listing_view', $html);
        $this->assertStringContainsString('inquiry_submitted', $html);
    }

    /**
     * The pin has to land where the coordinate says. Equirectangular:
     * x = (lng + 180)/360 * 720, y = (90 - lat)/180 * 360. Austin at
     * (30.26, -97.74) is x=164.52, y=119.48.
     */
    public function test_a_pin_is_plotted_at_its_actual_coordinate(): void
    {
        $this->event(['country' => 'US', 'city' => 'Austin', 'latitude' => 30.26, 'longitude' => -97.74]);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))->assertOk()->getContent();

        $this->assertStringContainsString('cx="164.52"', $html);
        $this->assertStringContainsString('cy="119.48"', $html);
    }

    /** Coordinates are rounded before grouping, or a busy city becomes confetti. */
    public function test_near_identical_coordinates_collapse_into_one_weighted_pin(): void
    {
        foreach (range(1, 5) as $i) {
            $this->event(['country' => 'US', 'city' => 'Austin', 'latitude' => 30.2601, 'longitude' => -97.7401]);
        }

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($this->mapSvg($html), '<circle cx='), 'Five events at one place should be one pin.');
        $this->assertStringContainsString('5 events', $html);
    }

    public function test_events_without_geo_do_not_become_phantom_pins(): void
    {
        $this->event([]);
        $this->event(['country' => 'US', 'latitude' => null, 'longitude' => null]);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))->assertOk()->getContent();

        $this->assertSame(0, substr_count($this->mapSvg($html), '<circle cx='));
    }

    /** Anonymised traffic is surfaced, not silently dropped from the map. */
    public function test_vpn_and_tor_traffic_is_counted_and_declared(): void
    {
        $this->event(['country' => 'US', 'latitude' => 30.26, 'longitude' => -97.74, 'is_vpn' => true]);
        $this->event(['country' => 'US', 'latitude' => 31.0, 'longitude' => -98.0, 'is_tor' => true]);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))->assertOk()->getContent();

        $this->assertStringContainsString('through a VPN', $html);
        $this->assertSame(2, substr_count($this->mapSvg($html), '<circle cx='), 'Anonymised events still get pins.');
    }

    public function test_the_window_filter_bounds_what_is_counted(): void
    {
        $this->event(['country' => 'US', 'city' => 'Recent'], 'page_view', 'v1', daysAgo: 2);
        $this->event(['country' => 'GB', 'city' => 'Ancient'], 'page_view', 'v2', daysAgo: 60);

        $week = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index', ['days' => 7]))->assertOk()->getContent();

        $this->assertStringContainsString('Recent', $week);
        $this->assertStringNotContainsString('Ancient', $week);

        $quarter = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index', ['days' => 90]))->assertOk()->getContent();

        $this->assertStringContainsString('Ancient', $quarter);
    }

    public function test_paid_arrivals_are_attributed_to_first_touch(): void
    {
        PpcVisitor::create([
            'visitor_id' => 'v-paid',
            'first_seen_at' => now()->subDay(),
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'vacation-properties-exact',
            'gclid' => 'ABC123',
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('vacation-properties-exact')
            ->assertSee('google');
    }

    public function test_it_exports_the_country_breakdown_as_csv(): void
    {
        $this->event(['country' => 'US'], 'page_view', 'v1');
        $this->event(['country' => 'GB'], 'page_view', 'v2');

        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.export', ['days' => 30]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Country,Events,Visitors,"Share %"', $csv);
        $this->assertStringContainsString('US,1,1', $csv);
        $this->assertStringContainsString('GB,1,1', $csv);
    }

    /** An empty window explains itself rather than rendering a blank map. */
    public function test_an_empty_window_says_why_it_is_empty(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('No located traffic in this window');
    }

    // ------------------------------------------------------------ authorisation

    public function test_it_is_closed_to_staff_without_the_permission(): void
    {
        $this->seed(RbacSeeder::class);

        $specialist = User::factory()->create([
            'role' => UserRole::ListingSpecialist,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($specialist)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($specialist)->get(route('admin.reports.export'))->assertForbidden();
    }

    public function test_export_is_gated_separately_from_viewing(): void
    {
        $this->seed(RbacSeeder::class);

        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        // Admin holds both, so both open — the point is that they are distinct
        // keys, so a custom role can be given view without export.
        $this->assertTrue($admin->hasPermission('reports.view'));
        $this->assertTrue($admin->hasPermission('reports.export'));

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
    }

    /** No token means no third-party call — the data still renders. */
    public function test_it_falls_back_to_a_plotted_grid_without_a_mapbox_token(): void
    {
        config()->set('services.mapbox.token', null);

        $this->event(['country' => 'US', 'latitude' => 30.26, 'longitude' => -97.74]);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('api.mapbox.com', $html);
        $this->assertStringContainsString('No Mapbox token configured', $html);
        $this->assertStringContainsString('<circle cx=', $this->mapSvg($html));
    }

    public function test_it_uses_mapbox_when_a_token_is_configured(): void
    {
        config()->set('services.mapbox.token', 'pk.test-token');

        $this->event(['country' => 'US', 'latitude' => 30.26, 'longitude' => -97.74]);

        $html = $this->actingAs($this->superAdmin())
            ->get(route('admin.reports.index'))->assertOk()->getContent();

        $this->assertStringContainsString('api.mapbox.com/mapbox-gl-js', $html);
        $this->assertStringContainsString('pk.test-token', $html);
        $this->assertStringContainsString('visitorMap', $html);
    }
}
