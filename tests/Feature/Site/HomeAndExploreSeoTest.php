<?php

namespace Tests\Feature\Site;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use Database\Seeders\ListoraSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two entry pages: home and Explore.
 *
 * SEO regressions are silent by nature — a page that loses its canonical or
 * its structured data renders identically and looks perfect in a browser. The
 * only way to notice is to assert on the head.
 */
class HomeAndExploreSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ListoraSeeder::class);
    }

    public function test_the_home_page_declares_its_canonical_and_identity(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.route('home').'"', $html);
        $this->assertStringContainsString('application/ld+json', $html);

        $graph = $this->jsonLdGraph($html);
        $types = array_column($graph, '@type');

        $this->assertContains('Organization', $types);
        $this->assertContains('WebSite', $types);
    }

    /**
     * The sitelinks search box has to resolve somewhere real. Pointing it at
     * the home page would send every search to a page that cannot answer one.
     */
    public function test_the_home_search_action_points_at_explore(): void
    {
        $graph = $this->jsonLdGraph($this->get('/')->getContent());
        $website = collect($graph)->firstWhere('@type', 'WebSite');

        $this->assertNotNull($website, 'No WebSite node in the home page graph.');
        $this->assertStringStartsWith(
            route('listings.index'),
            $website['potentialAction']['target']['urlTemplate'],
        );
        $this->assertStringContainsString('{search_term_string}', $website['potentialAction']['target']['urlTemplate']);
    }

    /**
     * A `sameAs` pointing nowhere is a broken claim about identity, so unset
     * social settings must be dropped rather than emitted blank.
     */
    public function test_unset_social_profiles_are_omitted_rather_than_blank(): void
    {
        $graph = $this->jsonLdGraph($this->get('/')->getContent());
        $organization = collect($graph)->firstWhere('@type', 'Organization');

        foreach ($organization['sameAs'] ?? [] as $url) {
            $this->assertNotSame('', trim($url), 'A blank sameAs URL was emitted.');
        }
    }

    public function test_explore_is_indexable_and_canonical_when_unfiltered(): void
    {
        $html = $this->get('/browse')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="robots" content="index, follow"', $html);
        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertStringContainsString('application/ld+json', $html);
    }

    /**
     * The point of ExploreSeo: a keyword search generates effectively unlimited
     * URLs, none of which should enter the index.
     */
    public function test_a_keyword_search_on_explore_is_not_indexable(): void
    {
        $html = $this->get('/browse?q=oceanfront')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex, follow"', $html);
    }

    public function test_an_empty_result_set_is_not_indexable(): void
    {
        Listing::query()->delete();

        $html = $this->get('/browse')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex, follow"', $html);
    }

    /**
     * The site-wide kill switch in Settings → SEO must win everywhere.
     *
     * Driven through SettingsRepository rather than by writing the row by
     * hand: that is the path the admin console uses, and it is what validates,
     * audits, and busts the cache. A hand-written row would prove the pages
     * read a column, not that the switch works.
     */
    public function test_the_site_wide_noindex_switch_overrides_both_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        app(SettingsRepository::class)->set('seo.robots_index', false, $admin);

        foreach (['/', '/browse'] as $path) {
            $this->assertStringContainsString(
                'content="noindex, follow"',
                $this->get($path)->getContent(),
                "{$path} ignored the site-wide indexing kill switch.",
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function jsonLdGraph(string $html): array
    {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $this->assertNotEmpty($matches, 'No JSON-LD block found.');

        $decoded = json_decode($matches[1], true);
        $this->assertIsArray($decoded, 'JSON-LD did not parse.');

        return $decoded['@graph'] ?? [];
    }
}
