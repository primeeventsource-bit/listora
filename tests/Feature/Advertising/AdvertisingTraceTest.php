<?php

namespace Tests\Feature\Advertising;

use App\Enums\AdEventType;
use App\Enums\UserRole;
use App\Models\AdEvent;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The administrative advertising trace.
 *
 * This screen shows visitor IP addresses, which the privacy policy restricts
 * to administrators for security and fraud investigation. The gate is
 * therefore the feature: a permission check that silently permits everyone
 * looks identical to one that works, right up until a reporting role reads
 * someone's address.
 */
class AdvertisingTraceTest extends TestCase
{
    use RefreshDatabase;

    private function eventFor(User $member, Listing $listing, string $ip, array $attributes = []): AdEvent
    {
        return AdEvent::create(array_merge([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => AdEventType::ListingView->value,
            'listing_id' => $listing->id,
            'member_user_id' => $member->id,
            'ad_number' => $member->ad_number,
            'listing_ref' => $listing->ad_number,
            'ip_address' => $ip,
            'visitor_id' => (string) Str::uuid(),
            'geo_city' => 'Orlando',
            'geo_region' => 'Florida',
            'geo_country' => 'US',
            'device_category' => 'mobile',
            'occurred_at' => now(),
        ], $attributes));
    }

    private function advertiserWithTraffic(string $ip = '203.0.113.42'): array
    {
        $member = User::factory()->create(['role' => UserRole::Owner, 'email_verified_at' => now()]);
        $listing = Listing::factory()->create(['owner_id' => $member->id]);
        $this->eventFor($member, $listing, $ip);

        return [$member, $listing];
    }

    public function test_a_super_admin_can_search_traffic_and_see_addresses(): void
    {
        $this->seed(RbacSeeder::class);
        [$member] = $this->advertiserWithTraffic();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get('/admin/advertising')
            ->assertOk()
            ->assertSee('203.0.113.42')
            ->assertSee($member->ad_number);
    }

    /**
     * The reason this permission exists at all.
     *
     * Someone who can see reporting must not thereby be able to read visitor
     * addresses. If this ever passes by accident, the policy's promise is
     * broken by a role assignment nobody thought of as a privacy decision.
     */
    public function test_reporting_access_alone_does_not_grant_the_trace(): void
    {
        $this->seed(RbacSeeder::class);
        $this->advertiserWithTraffic();

        $specialist = User::factory()->create([
            'role' => UserRole::ListingSpecialist,
            'email_verified_at' => now(),
        ]);

        $this->assertFalse(
            $specialist->hasPermission('advertising.trace'),
            'A listing specialist must not hold advertising.trace.'
        );

        $this->actingAs($specialist)->get('/admin/advertising')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get('/admin/advertising')->assertRedirect('/login');
    }

    public function test_searching_by_ad_number_narrows_to_that_advertiser(): void
    {
        $this->seed(RbacSeeder::class);

        [$wanted] = $this->advertiserWithTraffic('203.0.113.42');

        $otherMember = User::factory()->create(['role' => UserRole::Owner, 'email_verified_at' => now()]);
        $otherListing = Listing::factory()->create(['owner_id' => $otherMember->id]);
        $this->eventFor($otherMember, $otherListing, '198.51.100.9', ['geo_city' => 'Reykjavik']);

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get('/admin/advertising?q='.$wanted->ad_number)
            ->assertOk()
            ->assertSee('203.0.113.42')
            ->assertDontSee('198.51.100.9');
    }

    public function test_an_ip_prefix_sweeps_a_range(): void
    {
        $this->seed(RbacSeeder::class);
        $this->advertiserWithTraffic('203.0.113.42');

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get('/admin/advertising?ip=203.0.113.')
            ->assertOk()
            ->assertSee('203.0.113.42');
    }

    public function test_the_member_log_renders_chronologically(): void
    {
        $this->seed(RbacSeeder::class);
        [$member] = $this->advertiserWithTraffic();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get('/admin/advertising/member/'.$member->id)
            ->assertOk()
            ->assertSee($member->ad_number)
            ->assertSee('Listing viewed')
            ->assertSee('Orlando, Florida, US');
    }
}
