<?php

namespace Tests\Feature\Advertising;

use App\Enums\AdEventType;
use App\Models\AdEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The retention period is a promise to every visitor, so it is tested like one.
 *
 * Section 8 of the privacy policy says advertising traffic records are kept
 * for 24 months and then deleted, including the IP addresses in them. If
 * listora:prune-ad-events stops working, nothing breaks, no page errors, and
 * no one finds out - the site simply keeps data it told people it had deleted.
 * That is the failure this file exists to catch.
 */
class AdEventRetentionTest extends TestCase
{
    use RefreshDatabase;

    private function eventAgedMonths(int $months): AdEvent
    {
        return AdEvent::create([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => AdEventType::ListingView->value,
            'ip_address' => '203.0.113.9',
            'occurred_at' => now()->subMonths($months),
        ]);
    }

    public function test_records_past_the_retention_period_are_deleted(): void
    {
        $expired = $this->eventAgedMonths(25);

        $this->artisan('listora:prune-ad-events')->assertSuccessful();

        $this->assertDatabaseMissing('ad_events', ['id' => $expired->id]);
    }

    public function test_records_inside_the_retention_period_are_kept(): void
    {
        $recent = $this->eventAgedMonths(23);

        $this->artisan('listora:prune-ad-events')->assertSuccessful();

        $this->assertDatabaseHas('ad_events', ['id' => $recent->id]);
    }

    /**
     * The address goes with the row rather than surviving it. One window, one
     * promise - an address left behind in a "deleted" record would be the
     * exact thing section 8 says does not happen.
     */
    public function test_no_address_outlives_its_record(): void
    {
        $this->eventAgedMonths(30);
        $this->eventAgedMonths(26);
        $kept = $this->eventAgedMonths(1);

        $this->artisan('listora:prune-ad-events')->assertSuccessful();

        $this->assertSame(1, AdEvent::query()->count());
        $this->assertSame($kept->id, AdEvent::query()->first()->id);
    }

    public function test_a_dry_run_deletes_nothing(): void
    {
        $expired = $this->eventAgedMonths(36);

        $this->artisan('listora:prune-ad-events', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('ad_events', ['id' => $expired->id]);
    }
}
