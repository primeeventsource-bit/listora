<?php

namespace Tests\Feature\Site;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\PpcVisitor;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Explore is the page paid traffic lands on, which makes two classes of
 * mistake expensive rather than merely untidy:
 *
 *   - A robots or canonical slip lets a few hundred thousand facet URLs into
 *     the index competing with each other, and it is far cheaper to pin the
 *     policy here than to dig the site back out afterwards.
 *   - A tag that fires with no operator ID configured, or an ID that ships
 *     hardcoded, sends real traffic into someone's analytics from every
 *     developer laptop and every CI run.
 */
class ExplorePageSeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * These tests exercise Explore's SEO output for the vacation-week
     * category, which is currently withheld from the public site while payment
     * underwriting is in progress. The flag is turned on here so they keep
     * testing the SEO behaviour they were written for rather than silently
     * becoming tests of the category policy - which has its own file, in
     * TimeshareCategoryVisibilityTest.
     */
    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\FeatureFlag::query()->updateOrCreate(
            ['key' => 'timeshare_categories'],
            ['enabled' => true, 'scope' => 'global', 'rollout_pct' => 100],
        );

        \Illuminate\Support\Facades\Cache::flush();
    }

    private function publish(array $attributes = []): Listing
    {
        return Listing::factory()->create($attributes + [
            'status' => ListingStatus::Active->value,
            'published_at' => now(),
        ]);
    }

    private function setSetting(string $key, mixed $value): void
    {
        $actor = User::factory()->create(['role' => UserRole::SuperAdmin]);

        app(SettingsRepository::class)->set($key, $value, $actor);
    }

    // ------------------------------------------------------------------ meta

    public function test_the_title_and_heading_name_the_facet_not_the_page(): void
    {
        $this->publish(['kind' => Listing::KIND_WEEKS, 'mode' => 'rent', 'region' => 'Hawaii']);

        $html = $this->get('/browse?kind=weeks&mode=rent&region=Hawaii')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Vacation Weeks to Rent in Hawaii — Listora</title>', $html);

        // The visible H1 has to agree with the title — Google Ads scores
        // landing-page relevance on what the page says, not on the tag alone.
        $this->assertStringContainsString('<h1>Vacation Weeks to Rent in Hawaii</h1>', $html);
    }

    /**
     * The count is a separate clause, not a prefix on the category name, and
     * the mode fragment drops to lower case mid-sentence. Bolting a running
     * total onto an already-plural category produced "1 vacation weeks", and
     * reusing the title's fragment produced "to Rent" inside a sentence.
     */
    public function test_the_description_reads_as_a_sentence_for_a_single_result(): void
    {
        $this->publish(['kind' => Listing::KIND_WEEKS, 'mode' => 'rent', 'region' => 'Hawaii']);

        $html = $this->get('/browse?kind=weeks&mode=rent&region=Hawaii')->assertOk()->getContent();

        preg_match('#<meta name="description" content="(.*?)">#', $html, $m);
        $description = html_entity_decode($m[1]);

        $this->assertStringContainsString('1 live listing,', $description);
        $this->assertStringNotContainsString('1 live listings', $description);
        $this->assertStringContainsString('to rent in Hawaii', $description);
        $this->assertStringNotContainsString('to Rent in Hawaii —', $description);

        // Google renders roughly 155 characters; past that it is written for
        // nobody.
        $this->assertLessThanOrEqual(160, mb_strlen($description));
    }

    /**
     * Vacation properties are the only kind offered, so the unfiltered page
     * names them outright rather than saying "browse every listing". The
     * heading and the title still have to agree.
     */
    public function test_the_unfiltered_page_names_vacation_properties(): void
    {
        $this->publish();

        $this->get('/browse')
            ->assertOk()
            ->assertSee('<h1>Vacation Properties</h1>', false)
            ->assertSee('<title>Vacation Properties — Listora</title>', false)
            ->assertSee('Browse and inquire.', false);
    }

    // ------------------------------------------------------------- canonical

    /** Sort re-orders an identical result set, so it must not mint a URL. */
    public function test_canonical_drops_sort_but_keeps_the_facets(): void
    {
        $this->publish(['kind' => Listing::KIND_POINTS, 'region' => 'Mexico']);

        $html = $this->get('/browse?kind=points&region=Mexico&sort=price_low')->assertOk()->getContent();

        $canonical = route('listings.index', ['kind' => 'points', 'region' => 'Mexico']);

        $this->assertStringContainsString('<link rel="canonical" href="'.e($canonical).'">', $html);
        $this->assertStringNotContainsString('sort=price_low"', $html);
    }

    /** Page 3 holds different listings; it canonicalises to itself, not page 1. */
    public function test_canonical_is_self_referencing_on_a_later_page(): void
    {
        Listing::factory()->count(20)->create([
            'status' => ListingStatus::Active->value,
            'published_at' => now(),
        ]);

        $html = $this->get('/browse?page=2')->assertOk()->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="'.e(route('listings.index', ['page' => 2])).'">',
            $html,
        );
    }

    // ---------------------------------------------------------------- robots

    public function test_the_base_page_and_a_single_facet_are_indexable(): void
    {
        $this->publish(['kind' => Listing::KIND_HOME, 'region' => 'Florida']);

        foreach (['/browse', '/browse?kind=home', '/browse?kind=home&region=Florida'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('<meta name="robots" content="index, follow">', false);
        }
    }

    #[DataProvider('longTailUrls')]
    public function test_the_long_tail_is_kept_out_of_the_index(string $url): void
    {
        $this->publish(['kind' => Listing::KIND_HOME, 'mode' => 'rent', 'region' => 'Florida', 'bedrooms' => 3]);

        $this->get($url)
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    public static function longTailUrls(): array
    {
        return [
            'keyword search'   => ['/browse?q=oceanfront'],
            'non-default sort' => ['/browse?sort=price_low'],
            'three facets'     => ['/browse?kind=home&mode=rent&region=Florida'],
            'bedroom slice'    => ['/browse?kind=home&beds=3'],
        ];
    }

    /** A "nothing matched" page is a soft 404 and must never be indexable. */
    public function test_an_empty_result_set_is_not_indexable(): void
    {
        $this->get('/browse?region=Nowhere')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    /** A page may narrow the site-wide switch; it must never widen it. */
    public function test_the_site_wide_indexing_switch_overrides_the_page(): void
    {
        $this->publish(['kind' => Listing::KIND_HOME]);
        $this->setSetting('seo.robots_index', false);

        $this->get('/browse?kind=home')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, follow">', false);
    }

    // -------------------------------------------------------- structured data

    public function test_it_emits_valid_json_ld_describing_the_listings(): void
    {
        $listing = $this->publish(['title' => 'Oceanfront Two-Bedroom']);

        $html = $this->get('/browse')->assertOk()->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
        $this->assertNotEmpty($m, 'The page should carry a JSON-LD block.');

        $data = json_decode($m[1], true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error(), 'JSON-LD must parse.');

        $types = array_column($data['@graph'], '@type');
        $this->assertContains('CollectionPage', $types);
        $this->assertContains('BreadcrumbList', $types);
        $this->assertContains('ItemList', $types);

        $itemList = $data['@graph'][array_search('ItemList', $types, true)];
        $this->assertSame($listing->title, $itemList['itemListElement'][0]['name']);
        $this->assertSame(route('listings.show', $listing), $itemList['itemListElement'][0]['url']);
    }

    /**
     * Product/Offer markup tells Google the thing is purchasable and can put a
     * buy intent on it in the results. Listora advertises — the asking price is
     * the owner's number, not a transaction this site can complete.
     */
    public function test_it_never_claims_the_listings_are_purchasable(): void
    {
        $this->publish();

        $html = $this->get('/browse')->assertOk()->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        foreach (['"Product"', '"Offer"', 'priceCurrency', 'availability'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $m[1]);
        }
    }

    // ------------------------------------------------------------------- ppc

    public function test_no_analytics_tag_loads_when_no_id_is_configured(): void
    {
        $this->publish();

        $this->get('/browse')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('view_item_list', false);
    }

    public function test_the_item_list_payload_is_emitted_once_an_id_is_configured(): void
    {
        $listing = $this->publish([
            'kind' => Listing::KIND_WEEKS,
            'region' => 'Hawaii',
            'title' => 'Week 26 Oceanfront',
        ]);

        $this->setSetting('integrations.google_ads_id', 'AW-123456789');

        $html = $this->get('/browse?kind=weeks&region=Hawaii')->assertOk()->getContent();

        $this->assertStringContainsString('googletagmanager.com/gtag/js?id=AW-123456789', $html);
        $this->assertStringContainsString('view_item_list', $html);

        // Item ids are the listing reference, so an Ads audience can be
        // reconciled against what the database already knows.
        $this->assertStringContainsString($listing->reference, $html);
        $this->assertStringContainsString('explore_weeks_any_hawaii', $html);
    }

    // ----------------------------------------------------------- attribution

    public function test_a_google_ads_click_is_recorded_against_the_landing_page(): void
    {
        $this->publish();

        $response = $this->get('/browse?kind=home&gclid=TeSt-CLICK-123&utm_source=google&utm_medium=cpc&utm_campaign=weeks-hawaii');

        $response->assertOk()->assertCookie('lst_vid');

        $visitor = PpcVisitor::sole();

        $this->assertSame('TeSt-CLICK-123', $visitor->gclid);
        $this->assertSame('google', $visitor->utm_source);
        $this->assertSame('cpc', $visitor->utm_medium);
        $this->assertSame('weeks-hawaii', $visitor->utm_campaign);
        $this->assertStringContainsString('kind=home', $visitor->landing_url);
    }

    /**
     * Organic traffic has nothing to attribute, so it is not cookied. Setting
     * an identifier to learn nothing is not a trade worth making.
     */
    public function test_organic_traffic_is_neither_cookied_nor_recorded(): void
    {
        $this->publish();

        $this->get('/browse')
            ->assertOk()
            ->assertCookieMissing('lst_vid');

        $this->assertSame(0, PpcVisitor::count());
    }

    /** First touch means first: a later click must not overwrite the source. */
    public function test_a_returning_visitor_keeps_their_original_source(): void
    {
        $this->publish();

        $this->get('/browse?gclid=FIRST&utm_source=google&utm_campaign=original')->assertOk();

        $visitorId = PpcVisitor::sole()->visitor_id;

        // withUnencryptedCookie, not withCookie: `lst_vid` is exempt from cookie
        // encryption so it survives an APP_KEY rotation across its two-year
        // life (see bootstrap/app.php). withCookie would encrypt the value, the
        // middleware would read ciphertext, and this would look like a brand
        // new visitor. A real browser sends back exactly what was set.
        $this->withUnencryptedCookie('lst_vid', $visitorId)
            ->get('/browse?gclid=SECOND&utm_source=bing&utm_campaign=later')
            ->assertOk();

        $this->assertSame(1, PpcVisitor::count());
        $this->assertSame('FIRST', PpcVisitor::sole()->gclid);
        $this->assertSame('original', PpcVisitor::sole()->utm_campaign);
    }
}
