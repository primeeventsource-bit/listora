<?php

namespace Tests\Feature\Site;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Database\Seeders\ListoraSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A freshly seeded site must actually show something.
 *
 * `Listing::scopePublished()` requires BOTH `status = active` and a
 * `published_at`. The seeder used to set only the timestamp, which left every
 * row at the column default of 'draft' — so `listings` was full, every query
 * was correct, and the home page and browse were silently empty. Nothing
 * errored; there was simply nothing to see.
 *
 * That is the failure this guards: a bug that looks like a blank page rather
 * than a stack trace, and that only appears after the exact command the
 * deployment guide tells you to run.
 */
class SeededSiteIsVisibleTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_listings_are_actually_published(): void
    {
        $this->seed(ListoraSeeder::class);

        $total = Listing::count();
        $this->assertGreaterThan(0, $total, 'The seeder produced no listings at all.');

        // Asserted on the two columns rather than through scopePublished,
        // which now also withholds the points and vacation-week categories
        // while payment underwriting is in progress. The failure this guards
        // is a seeder that sets one of these and not the other, producing rows
        // that exist and appear nowhere.
        $this->assertSame(
            $total,
            Listing::query()->where('status', ListingStatus::Active->value)->whereNotNull('published_at')->count(),
            'Seeded listings are not live — check that the seeder sets BOTH status and published_at.',
        );

        // And the categories that are on offer do reach the public scope.
        $this->assertSame(
            Listing::query()->where('kind', Listing::KIND_HOME)->count(),
            Listing::published()->count(),
            'Vacation properties should all be publicly visible.',
        );
    }

    public function test_seeded_listings_carry_their_advertising_term(): void
    {
        $this->seed(ListoraSeeder::class);

        // Without expires_at these never surface in the "term ending soon"
        // views operations depends on, and nothing would ever expire.
        $this->assertSame(
            0,
            Listing::whereNull('expires_at')->count(),
            'Every seeded listing should carry a term end date.',
        );

        $this->assertSame(
            0,
            Listing::whereNull('verified_at')->count(),
            'Seeded listings stand in for verified ones and should say so.',
        );
    }

    public function test_the_home_page_and_browse_show_the_seeded_listings(): void
    {
        $this->seed(ListoraSeeder::class);

        // Browse paginates under its own `recommended` ordering — featured
        // first, then plan rank, then views — so the lowest-id listing is not
        // necessarily on page one. Ask for the listing browse itself would
        // show first, or this asserts against a page it was never on.
        $listing = Listing::published()->sorted(null)->first();

        // Seeded listings carry no owner_id, so they have no member
        // advertising number and keep the legacy address. That is the
        // fallback working, not a defect - and it is why this asserts the
        // listing's own publicUrl() rather than a hard-coded prefix.
        // A prefix, not this listing's URL: the home page shows featured
        // listings, which are not necessarily the one browse would lead with.
        $this->get('/')->assertOk()->assertSee('/listing/', false);
        $this->get('/browse')->assertOk()->assertSee($listing->title, false);
        $this->get($listing->publicUrl())->assertOk();
    }

    /**
     * An owned listing lives at the advertising URL, and the old address
     * redirects there permanently.
     *
     * A listing URL is the kind of thing that gets shared, bookmarked and
     * printed. /listing/{slug} was public for the site's whole life before
     * advertising numbers existed, so it redirects rather than 404ing - and
     * permanently matters, because a 302 would leave search engines indexing
     * the old address indefinitely.
     */
    public function test_an_owned_listing_lives_at_its_advertising_url(): void
    {
        $owner = \App\Models\User::factory()->create();
        $listing = Listing::factory()->create(['owner_id' => $owner->id]);

        $canonical = "/ad/{$owner->ad_number}/{$listing->ad_number}";

        $this->assertSame(url($canonical), $listing->publicUrl());
        $this->get($canonical)->assertOk()->assertSee($listing->title, false);

        $this->get('/listing/'.$listing->slug)
            ->assertStatus(301)
            ->assertRedirect($listing->publicUrl());
    }

    /**
     * The number pair is the address. A listing that exists but belongs to a
     * different advertiser must 404 rather than redirect to its real URL -
     * correcting it would let anyone map listings to members by trying pairs.
     */
    public function test_a_listing_under_the_wrong_advertiser_is_not_found(): void
    {
        $owner = \App\Models\User::factory()->create();
        $stranger = \App\Models\User::factory()->create();
        $listing = Listing::factory()->create(['owner_id' => $owner->id]);

        $this->get("/ad/{$stranger->ad_number}/{$listing->ad_number}")->assertNotFound();
    }
}
