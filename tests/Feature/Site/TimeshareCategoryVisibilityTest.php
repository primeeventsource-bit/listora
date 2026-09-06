<?php

namespace Tests\Feature\Site;

use App\Models\FeatureFlag;
use App\Models\Listing;
use App\Models\ListingDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Points and vacation-week categories are withheld from the public site while
 * payment underwriting is in progress.
 *
 * This is a compliance posture, not a preference, so it is tested as one. The
 * listings are not deleted - they stay in the catalogue, owners keep them, and
 * the flag turns them back on without a deploy. What must not happen is one of
 * them appearing on a public surface while the flag is off.
 */
class TimeshareCategoryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function listingOfKind(string $kind, string $title): Listing
    {
        return Listing::factory()->create(['kind' => $kind, 'title' => $title]);
    }

    private function enableTimeshare(): void
    {
        FeatureFlag::query()->updateOrCreate(
            ['key' => 'timeshare_categories'],
            ['enabled' => true, 'scope' => 'global', 'rollout_pct' => 100],
        );

        // FeatureFlagService caches the whole flag table, so a row written
        // behind its back is not seen until the cache is dropped. Production
        // busts this on update(); a test writing the row directly has to.
        Cache::flush();
    }

    public function test_points_and_week_listings_are_hidden_from_public_queries(): void
    {
        $this->listingOfKind(Listing::KIND_HOME, 'A Whole House');
        $this->listingOfKind(Listing::KIND_POINTS, 'A Points Balance');
        $this->listingOfKind(Listing::KIND_WEEKS, 'A Calendar Week');

        $visible = Listing::published()->pluck('title');

        $this->assertContains('A Whole House', $visible->all());
        $this->assertNotContains('A Points Balance', $visible->all());
        $this->assertNotContains('A Calendar Week', $visible->all());
    }

    public function test_they_are_hidden_on_browse_and_the_home_page(): void
    {
        $this->listingOfKind(Listing::KIND_POINTS, 'A Points Balance');
        $this->listingOfKind(Listing::KIND_WEEKS, 'A Calendar Week');

        foreach (['/', '/browse', '/inventory'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertDontSee('A Points Balance')
                ->assertDontSee('A Calendar Week');
        }
    }

    /**
     * A direct link must not be a way round it. Someone reviewing the site
     * will follow URLs, and a hidden category reachable by its own address is
     * not hidden.
     */
    public function test_a_direct_link_to_a_hidden_listing_does_not_resolve(): void
    {
        $points = $this->listingOfKind(Listing::KIND_POINTS, 'A Points Balance');

        $this->get('/listing/'.$points->slug)->assertNotFound();
    }

    /** The categories are withheld, not deleted. The flag brings them back. */
    public function test_the_flag_turns_them_back_on(): void
    {
        $this->listingOfKind(Listing::KIND_POINTS, 'A Points Balance');

        $this->assertNotContains('A Points Balance', Listing::published()->pluck('title')->all());

        $this->enableTimeshare();

        $this->assertContains('A Points Balance', Listing::published()->pluck('title')->all());
    }

    /**
     * The flag fails CLOSED, unlike every other flag in this codebase. With no
     * row at all the categories stay hidden, because the cost of wrongly
     * showing them is a failed underwriting review.
     */
    public function test_a_missing_flag_row_hides_rather_than_reveals(): void
    {
        FeatureFlag::query()->where('key', 'timeshare_categories')->delete();

        $this->listingOfKind(Listing::KIND_POINTS, 'A Points Balance');

        $this->assertNotContains('A Points Balance', Listing::published()->pluck('title')->all());
    }
    /**
     * Hiding a category from a dropdown is not withholding it.
     *
     * The property information sheet went on offering points and vacation
     * weeks after the catalogue stopped publishing them, and accepted a
     * submission naming one - so somebody could request advertising in a
     * category the site does not sell. A real submission arrived that way.
     *
     * The sheet has since stopped asking the question at all, so the guarantee
     * moved rather than went away: a posted category is ignored and the draft
     * is filed as a vacation property. What must never happen is a draft
     * carrying a withheld category, however it was posted.
     */
    public function test_a_withheld_category_cannot_be_requested(): void
    {
        $this->post("/property-information", [
            "kind" => Listing::KIND_POINTS,
            "mode" => "own",
            "owner_name" => "Dana Whitfield",
            "owner_email" => "dana@example.test",
        ]);

        $this->assertSame(Listing::KIND_HOME, ListingDraft::sole()->kind);

        $this->assertSame(
            0,
            ListingDraft::whereIn('kind', [Listing::KIND_POINTS, Listing::KIND_WEEKS])->count(),
        );
    }

    public function test_an_offered_category_still_can_be(): void
    {
        $this->post("/property-information", [
            "kind" => Listing::KIND_HOME,
            "mode" => "rent",
            "owner_name" => "Dana Whitfield",
            "owner_email" => "dana@example.test",
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, ListingDraft::count());
    }

    /** The form must not list what it will refuse. */
    public function test_the_sheet_does_not_offer_withheld_categories(): void
    {
        $html = $this->get("/property-information")->assertOk()->getContent();

        $this->assertStringNotContainsString(">Resort Club Points<", $html);
        $this->assertStringNotContainsString(">Vacation Weeks<", $html);
    }
    /**
     * The whole public site, read as source rather than as rendered text.
     *
     * Three earlier sweeps declared this clean by checking visible copy. A
     * payment underwriter read the HTML and classified the business on what
     * was in meta tags, structured data, hidden inputs, a config blurb and a
     * contact-form dropdown - none of which a visitor ever sees. This checks
     * the thing that was actually being read.
     */
    public function test_no_withheld_category_appears_anywhere_in_the_public_source(): void
    {
        $terms = [
            "resort club points", "club points", "points balance", "vacation week",
            "timeshare", "club statement", "club_name", "week_number", "usage year",
            "points package", "deeded week",
        ];

        $pages = [
            "/", "/browse", "/how-it-works", "/pricing", "/about", "/help",
            "/inventory", "/list-your-property", "/property-information",
            "/legal/tos", "/legal/privacy", "/legal/advertising-agreement",
        ];

        $found = [];

        foreach ($pages as $page) {
            $html = strtolower($this->get($page)->getContent());

            foreach ($terms as $term) {
                if (str_contains($html, $term)) {
                    $found[] = $page . " contains " . $term;
                }
            }
        }

        $this->assertSame([], $found, "Withheld categories are still in the page source:" . PHP_EOL . implode(PHP_EOL, $found));
    }
}
