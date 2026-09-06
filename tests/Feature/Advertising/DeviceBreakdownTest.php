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
 * The device breakdown on the advertiser's dashboard.
 *
 * Crawler traffic is counted under Desktop rather than given its own row. The
 * events are kept - nothing is discarded, and the breakdown still totals to
 * the view count beside it - so what changed is the label, not the data.
 *
 * Admin reporting still reads `bot` for what it is. That is the point of
 * folding it in only one place: "how much of this is crawlers" stays
 * answerable by whoever needs to ask it.
 */
class DeviceBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private function recordView(Listing $listing, string $device): AdEvent
    {
        return AdEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => AdEventType::ListingView->value,
            'listing_id' => $listing->id,
            'member_user_id' => $listing->owner_id,
            'ad_number' => $listing->owner->ad_number,
            'listing_ref' => $listing->ad_number,
            'ip_address' => '203.0.113.5',
            'visitor_id' => (string) Str::uuid(),
            'device_category' => $device,
            'occurred_at' => now(),
        ]);
    }

    public function test_bot_traffic_is_counted_as_desktop_and_never_named(): void
    {
        $advertiser = User::factory()->create([
            'role' => UserRole::Owner,
            'email_verified_at' => now(),
        ]);

        $listing = Listing::factory()->create(['owner_id' => $advertiser->id]);

        $this->recordView($listing, 'desktop');
        $this->recordView($listing, 'desktop');
        $this->recordView($listing, 'bot');
        $this->recordView($listing, 'mobile');

        $html = $this->actingAs($advertiser)->get('/dashboard')->assertOk()->getContent();

        // Three desktop: two real plus the crawler folded in. Nothing dropped.
        $this->assertMatchesRegularExpression('/Desktop<\/td>\s*<td[^>]*>3</', $html);
        $this->assertMatchesRegularExpression('/Mobile<\/td>\s*<td[^>]*>1</', $html);

        $this->assertStringNotContainsString('>Bot<', $html);
    }

    /** The rows are still there, so the totals must still agree. */
    public function test_the_breakdown_totals_to_the_views_figure(): void
    {
        $advertiser = User::factory()->create([
            'role' => UserRole::Owner,
            'email_verified_at' => now(),
        ]);

        $listing = Listing::factory()->create(['owner_id' => $advertiser->id]);

        foreach (['desktop', 'bot', 'bot', 'mobile'] as $device) {
            $this->recordView($listing, $device);
        }

        $performance = app(\App\Services\Advertising\MemberPerformance::class)
            ->forRequest(request(), $advertiser->id);

        $this->assertSame(4, $performance['totals']['views']);
        $this->assertSame(4, array_sum($performance['devices']));
        $this->assertSame(3, $performance['devices']['desktop']);
        $this->assertArrayNotHasKey('bot', $performance['devices']);
    }
}
