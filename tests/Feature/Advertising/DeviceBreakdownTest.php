<?php

namespace Tests\Feature\Advertising;

use App\Enums\AdEventType;
use App\Enums\UserRole;
use App\Models\AdEvent;
use App\Models\Listing;
use App\Models\User;
use App\Support\UserAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Crawler traffic is recorded and reported as desktop.
 *
 * Not hidden and not dropped - counted. A click bought from an ad network is
 * billed whether a person or a bot made it, so the visit belongs in the
 * figures like any other. What it does not need is a heading of its own on
 * every screen.
 *
 * Classified that way at the source rather than folded per screen, so the
 * stored value and every report agree. Anything needing the distinction still
 * has UserAgent::isBot().
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

    public function test_a_crawler_is_classified_as_desktop(): void
    {
        $agents = [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'facebookexternalhit/1.1',
            'curl/8.4.0',
            'python-requests/2.31.0',
        ];

        foreach ($agents as $agent) {
            $this->assertSame(
                'desktop',
                UserAgent::parse($agent)['device_category'],
                "{$agent} should be recorded as desktop.",
            );

            // The distinction still exists for anything that needs it.
            $this->assertTrue(UserAgent::isBot($agent));
        }
    }

    /** A person on a phone is still a phone. Folding bots must not blur that. */
    public function test_real_devices_are_still_told_apart(): void
    {
        $this->assertSame(
            'mobile',
            UserAgent::parse('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148')['device_category'],
        );

        $this->assertSame(
            'desktop',
            UserAgent::parse('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120')['device_category'],
        );
    }

    public function test_no_screen_reports_a_bot_category(): void
    {
        $advertiser = User::factory()->create([
            'role' => UserRole::Owner,
            'email_verified_at' => now(),
        ]);

        $listing = Listing::factory()->create(['owner_id' => $advertiser->id]);

        $this->recordView($listing, 'desktop');
        $this->recordView($listing, 'desktop');
        $this->recordView($listing, 'mobile');

        $html = $this->actingAs($advertiser)->get('/dashboard')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/Desktop<\/td>\s*<td[^>]*>2</', $html);
        $this->assertMatchesRegularExpression('/Mobile<\/td>\s*<td[^>]*>1</', $html);
        $this->assertStringNotContainsString('>Bot<', $html);
    }

    /** Nothing is discarded, so the breakdown has to total the view figure. */
    public function test_the_breakdown_totals_to_the_views_figure(): void
    {
        $advertiser = User::factory()->create([
            'role' => UserRole::Owner,
            'email_verified_at' => now(),
        ]);

        $listing = Listing::factory()->create(['owner_id' => $advertiser->id]);

        foreach (['desktop', 'desktop', 'desktop', 'mobile'] as $device) {
            $this->recordView($listing, $device);
        }

        $performance = app(\App\Services\Advertising\MemberPerformance::class)
            ->forRequest(request(), $advertiser->id);

        $this->assertSame(4, $performance['totals']['views']);
        $this->assertSame(4, array_sum($performance['devices']));
        $this->assertArrayNotHasKey('bot', $performance['devices']);
    }
}
