<?php

namespace Tests\Feature\Advertising;

use App\Enums\AdEventType;
use App\Models\AdEvent;
use App\Models\Listing;
use App\Models\User;
use App\Services\Advertising\AdTrafficSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recording visits to advertising pages.
 *
 * The table exists to evidence that a member's advertisement ran and was
 * reachable, so these assert the two things that make it evidence: that the
 * visit is attributed to the right advertiser and listing, and that recording
 * cannot expose a visitor's address to that advertiser.
 */
class AdEventRecordingTest extends TestCase
{
    use RefreshDatabase;

    private function advertisedListing(): Listing
    {
        $owner = User::factory()->create();

        return Listing::factory()->create(['owner_id' => $owner->id]);
    }

    public function test_visiting_an_advertising_url_records_the_visit(): void
    {
        $listing = $this->advertisedListing();
        $owner = $listing->owner;

        $this->get("/ad/{$owner->ad_number}/{$listing->ad_number}")->assertOk();

        $event = AdEvent::query()->latest('id')->first();

        $this->assertNotNull($event, 'The visit should have been recorded.');
        $this->assertSame(AdEventType::ListingView, $event->event_type);
        $this->assertSame($owner->ad_number, $event->ad_number);
        $this->assertSame($listing->ad_number, $event->listing_ref);
        $this->assertSame($listing->id, $event->listing_id);
        $this->assertSame($owner->id, $event->member_user_id);
    }

    public function test_an_advertisers_view_of_their_own_traffic_excludes_the_visitor_address(): void
    {
        $listing = $this->advertisedListing();
        $owner = $listing->owner;

        $this->get("/ad/{$owner->ad_number}/{$listing->ad_number}")->assertOk();

        $event = AdEvent::query()->forMember($owner->id)->first();

        $this->assertNotNull($event);

        // Not "is null" - absent from the result entirely, so a view that
        // renders whatever it is handed cannot leak it.
        $this->assertArrayNotHasKey('ip_address', $event->getAttributes());

        // The address was still recorded, for admins.
        $this->assertNotNull(AdEvent::query()->latest('id')->first()->ip_address);
    }

    public function test_a_paid_click_is_attributed_to_its_network(): void
    {
        $listing = $this->advertisedListing();
        $owner = $listing->owner;

        $this->get("/ad/{$owner->ad_number}/{$listing->ad_number}?gclid=abc123");

        $event = AdEvent::query()->latest('id')->first();

        $this->assertSame(AdTrafficSource::GOOGLE_ADS, $event->source_category);
        $this->assertSame('abc123', $event->click_id);
    }

    /**
     * A click id outranks campaign tagging. A link tagged utm_source=newsletter
     * that arrives carrying a gclid came from Google Ads, whatever the tag
     * says - and counting paid traffic as email is the error that makes
     * advertising reporting worthless.
     */
    public function test_a_network_click_id_outranks_a_mistagged_campaign(): void
    {
        $listing = $this->advertisedListing();
        $owner = $listing->owner;

        $this->get("/ad/{$owner->ad_number}/{$listing->ad_number}?utm_source=newsletter&utm_medium=email&gclid=xyz");

        $event = AdEvent::query()->latest('id')->first();

        $this->assertSame(AdTrafficSource::GOOGLE_ADS, $event->source_category);
        // The raw tag is still kept, so the mistagging stays visible.
        $this->assertSame('newsletter', $event->utm_source);
    }

    public function test_the_advertisers_index_page_records_a_view(): void
    {
        $listing = $this->advertisedListing();

        $this->get("/ad/{$listing->owner->ad_number}")->assertOk();

        $this->assertSame(
            AdEventType::AdView,
            AdEvent::query()->latest('id')->first()->event_type
        );
    }
}
