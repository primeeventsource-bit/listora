<?php

namespace Tests\Feature\Site;

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

        $this->assertSame(
            $total,
            Listing::published()->count(),
            'Seeded listings are not visible to scopePublished — check that the seeder sets '
            .'BOTH status and published_at.',
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

        $listing = Listing::published()->first();

        $this->get('/')->assertOk()->assertSee('/listing/', false);
        $this->get('/browse')->assertOk()->assertSee($listing->title, false);
        $this->get('/listing/'.$listing->slug)->assertOk();
    }
}
