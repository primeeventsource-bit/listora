<?php

namespace Tests\Feature\Advertising;

use App\Enums\AdEventType;
use App\Enums\UserRole;
use App\Models\AdEvent;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The advertiser's performance page.
 *
 * Section 5 of the privacy policy now tells every visitor, in public, that an
 * advertiser sees the approximate location of visits to their listings and
 * never sees an IP address. This file is what keeps that sentence true: the
 * page is a tenant boundary and a privacy boundary at once, and neither
 * failure would be visible by looking at it.
 */
class MemberPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function advertiser(string $email): User
    {
        return User::factory()->create([
            'role' => UserRole::Owner,
            'email' => $email,
            'email_verified_at' => now(),
        ]);
    }

    private function recordView(Listing $listing, string $ip, array $attributes = []): AdEvent
    {
        return AdEvent::create(array_merge([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => AdEventType::ListingView->value,
            'listing_id' => $listing->id,
            'member_user_id' => $listing->owner_id,
            'ad_number' => $listing->owner->ad_number,
            'listing_ref' => $listing->ad_number,
            'ip_address' => $ip,
            'visitor_id' => (string) Str::uuid(),
            'geo_city' => 'Orlando',
            'geo_region' => 'Florida',
            'geo_country' => 'US',
            'geo_lat' => 28.4883,
            'geo_lng' => -81.4061,
            'device_category' => 'mobile',
            'occurred_at' => now(),
        ], $attributes));
    }

    public function test_an_advertiser_sees_their_own_totals_and_approximate_places(): void
    {
        $advertiser = $this->advertiser('mine@listora1.test');
        $listing = Listing::factory()->create(['owner_id' => $advertiser->id]);

        $this->recordView($listing, '203.0.113.5');
        $this->recordView($listing, '203.0.113.6');

        $this->actingAs($advertiser)
            ->get('/account/performance')
            ->assertOk()
            ->assertSee($advertiser->ad_number)
            ->assertSee('Orlando, Florida, US')
            // Labelled approximate on the page, not only in the policy.
            ->assertSee('Approximate');
    }

    /**
     * The sentence in the privacy policy, asserted.
     *
     * A visitor's address must not reach the advertiser's screen. This would
     * fail silently in the worst way - the page would look completely normal
     * and be quietly leaking.
     */
    public function test_a_visitors_ip_address_never_reaches_the_advertiser(): void
    {
        $advertiser = $this->advertiser('mine@listora1.test');
        $listing = Listing::factory()->create(['owner_id' => $advertiser->id]);

        $this->recordView($listing, '198.51.100.77');

        $this->actingAs($advertiser)
            ->get('/account/performance')
            ->assertOk()
            ->assertDontSee('198.51.100.77');
    }

    public function test_one_advertiser_never_sees_another_advertisers_traffic(): void
    {
        $mine = $this->advertiser('mine@listora1.test');
        $theirs = $this->advertiser('theirs@listora1.test');

        $myListing = Listing::factory()->create(['owner_id' => $mine->id, 'title' => 'My Property']);
        $theirListing = Listing::factory()->create(['owner_id' => $theirs->id, 'title' => 'Their Property']);

        $this->recordView($myListing, '203.0.113.5');
        $this->recordView($theirListing, '203.0.113.9', ['geo_city' => 'Reykjavik', 'geo_region' => 'Capital']);
        $this->recordView($theirListing, '203.0.113.10', ['geo_city' => 'Reykjavik', 'geo_region' => 'Capital']);

        $html = $this->actingAs($mine)->get('/account/performance')->assertOk()->getContent();

        $this->assertStringContainsString('My Property', $html);
        $this->assertStringNotContainsString('Their Property', $html);
        $this->assertStringNotContainsString('Reykjavik', $html);
    }

    /**
     * A listing id belonging to someone else must not act as a filter. Without
     * the ownership check it would report their traffic through this
     * advertiser's own page.
     */
    public function test_filtering_by_someone_elses_listing_reports_nothing_of_theirs(): void
    {
        $mine = $this->advertiser('mine@listora1.test');
        $theirs = $this->advertiser('theirs@listora1.test');

        $theirListing = Listing::factory()->create(['owner_id' => $theirs->id]);
        $this->recordView($theirListing, '203.0.113.9', ['geo_city' => 'Reykjavik']);

        $this->actingAs($mine)
            ->get('/account/performance?listing='.$theirListing->id)
            ->assertOk()
            ->assertDontSee('Reykjavik');
    }

    public function test_the_period_filter_excludes_older_traffic(): void
    {
        $advertiser = $this->advertiser('mine@listora1.test');
        $listing = Listing::factory()->create(['owner_id' => $advertiser->id]);

        $this->recordView($listing, '203.0.113.5', ['occurred_at' => now()->subDays(45)]);

        // Present in a 90-day window, absent from a 7-day one.
        $this->actingAs($advertiser)
            ->get('/account/performance?range=90d')
            ->assertOk()
            ->assertSee('Orlando, Florida, US');

        $this->actingAs($advertiser)
            ->get('/account/performance?range=7d')
            ->assertOk()
            ->assertDontSee('Orlando, Florida, US');
    }
}
