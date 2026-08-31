<?php

namespace Tests\Feature\Advertising;

use App\Models\Listing;
use App\Models\User;
use App\Support\AdNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Advertising numbers.
 *
 * The format is YYYYMMDDHHMM with no seconds, so two rows created in the same
 * minute want the same number. That is the whole risk in this feature: a
 * duplicate would either throw at the unique index during an admin's listing
 * creation, or - worse, if the index were missing - silently point two
 * advertising URLs at each other.
 */
class AdNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_account_is_given_a_twelve_digit_number(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression('/^\d{12}$/', $user->ad_number);
    }

    public function test_a_new_listing_is_given_its_own_number(): void
    {
        $owner = User::factory()->create();
        $listing = Listing::factory()->create(['owner_id' => $owner->id]);

        $this->assertMatchesRegularExpression('/^\d{12}$/', $listing->ad_number);

        // Two sequences, not one shared counter: the listing does not inherit
        // or extend the advertiser's number.
        $this->assertNotSame($owner->ad_number, $listing->ad_number);
    }

    /**
     * The case the format cannot express. Several accounts made inside one
     * minute must still come out with distinct numbers.
     */
    public function test_accounts_created_in_the_same_minute_do_not_collide(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-31 02:53:00'));

        $numbers = collect(range(1, 5))
            ->map(fn () => User::factory()->create()->ad_number);

        Carbon::setTestNow();

        $this->assertCount(5, $numbers->unique(), 'Every account needs its own number.');

        // The walk goes forward a minute at a time from the requested moment,
        // so the first keeps the true minute and the rest follow it.
        $this->assertSame('202608310253', $numbers->first());
        $this->assertSame('202608310257', $numbers->last());
    }

    public function test_the_number_is_stable_once_assigned(): void
    {
        $user = User::factory()->create();
        $original = $user->ad_number;

        $user->update(['name' => 'Renamed Advertiser']);

        $this->assertSame($original, $user->fresh()->ad_number);
    }

    public function test_a_number_describes_the_moment_it_encodes(): void
    {
        $this->assertSame('31 Aug 2026, 02:53', AdNumber::describe('202608310253'));
        $this->assertNull(AdNumber::describe('not-a-number'));
        $this->assertTrue(AdNumber::looksValid('202608310253'));
        $this->assertFalse(AdNumber::looksValid('20260831025'));
    }
}
