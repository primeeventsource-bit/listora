<?php

namespace Tests\Feature\Advertising;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The demo advertiser command.
 *
 * It writes to production, so the properties that matter are that running it
 * twice does not duplicate anything, that what it creates is genuinely public
 * (both status and published_at, or the listings exist and appear nowhere),
 * and that --remove really removes it rather than leaving ownerless rows
 * behind on the site.
 */
class DemoAdvertisersTest extends TestCase
{
    use RefreshDatabase;

    private function demoUsers()
    {
        return User::query()->where('email', 'like', '%@listora1.example');
    }

    public function test_it_creates_five_live_listings_across_three_advertisers(): void
    {
        $this->artisan('listora:demo-advertisers')->assertSuccessful();

        $this->assertSame(3, $this->demoUsers()->count());

        $listings = Listing::query()->whereIn('owner_id', $this->demoUsers()->pluck('id'))->get();

        $this->assertCount(5, $listings);

        // Public means both axes. A listing with a status and no published_at
        // exists and appears nowhere, which reads as a bug in the site.
        foreach ($listings as $listing) {
            $this->assertNotNull($listing->published_at, "{$listing->title} has no published_at.");
            $this->assertTrue(
                Listing::published()->whereKey($listing->id)->exists(),
                "{$listing->title} is not publicly visible."
            );
        }
    }

    public function test_every_demo_listing_has_a_working_advertising_url(): void
    {
        $this->artisan('listora:demo-advertisers')->assertSuccessful();

        $listings = Listing::query()
            ->whereIn('owner_id', $this->demoUsers()->pluck('id'))
            ->with('owner')
            ->get();

        foreach ($listings as $listing) {
            $this->get("/ad/{$listing->owner->ad_number}/{$listing->ad_number}")
                ->assertOk()
                ->assertSee($listing->title, false);
        }
    }

    /** One advertiser holds several listings, which is what the numbering is for. */
    public function test_one_advertiser_carries_more_than_one_listing(): void
    {
        $this->artisan('listora:demo-advertisers')->assertSuccessful();

        $counts = Listing::query()
            ->whereIn('owner_id', $this->demoUsers()->pluck('id'))
            ->get()
            ->groupBy('owner_id')
            ->map->count();

        $this->assertGreaterThan(1, $counts->max(), 'No advertiser has multiple listings.');
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $this->artisan('listora:demo-advertisers')->assertSuccessful();
        $this->artisan('listora:demo-advertisers')->assertSuccessful();

        $this->assertSame(3, $this->demoUsers()->count());
        $this->assertSame(5, Listing::query()->whereIn('owner_id', $this->demoUsers()->pluck('id'))->count());
    }

    public function test_remove_takes_the_listings_with_the_accounts(): void
    {
        $this->artisan('listora:demo-advertisers')->assertSuccessful();
        $this->artisan('listora:demo-advertisers', ['--remove' => true])->assertSuccessful();

        $this->assertSame(0, $this->demoUsers()->count());

        // The failure worth guarding: deleting the accounts but leaving their
        // listings would put ownerless demo rows back on the public site.
        $this->assertSame(0, Listing::query()->where('slug', 'like', 'demo-%')->count());
    }

    public function test_without_a_password_the_accounts_cannot_be_signed_into(): void
    {
        $this->artisan('listora:demo-advertisers')->assertSuccessful();

        $user = $this->demoUsers()->first();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_with_a_password_they_can_sign_in(): void
    {
        $this->artisan('listora:demo-advertisers', ['--password' => 'demo-advertiser-2026'])
            ->assertSuccessful();

        $user = $this->demoUsers()->first();

        $this->post('/login', ['email' => $user->email, 'password' => 'demo-advertiser-2026'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }
}
