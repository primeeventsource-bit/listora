<?php

namespace Tests\Feature\Site;

use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * How long an offer stays open.
 *
 * The window was stated in three places that disagreed: the scheduler comment
 * said 24 hours, Offer::EXPIRY_HOURS said 72, and the settings default said 72.
 * The support assistant quoted its own fallback of 72 on top of that. An owner
 * reading one number and an offer behaving by another is a dispute waiting to
 * happen, so the value is pinned here rather than left to whichever constant a
 * future reader finds first.
 */
class OfferExpiryWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_offer_expires_twenty_four_hours_after_it_is_made(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 09:00:00'));

        $owner = User::factory()->create();
        $listing = Listing::factory()->create(['owner_id' => $owner->id, 'kind' => Listing::KIND_HOME]);

        $this->post(route('offers.store', $listing), [
            'name' => 'Dana Reeve',
            'email' => 'dana@example.test',
            'offer_amount' => 4200,
            'message' => 'Happy to move quickly if this works for you.',
        ])->assertRedirect();

        $offer = Offer::query()->latest('id')->firstOrFail();

        $this->assertSame(
            '2026-09-03 09:00:00',
            $offer->expires_at->format('Y-m-d H:i:s'),
            'An offer should stay open for 24 hours, not longer.'
        );

        Carbon::setTestNow();
    }

    /** The constant is the single source the other three places defer to. */
    public function test_the_documented_window_is_twenty_four_hours(): void
    {
        $this->assertSame(24, Offer::EXPIRY_HOURS);
        $this->assertSame(24, (int) setting('offers.expiry_hours', Offer::EXPIRY_HOURS));
    }
}
