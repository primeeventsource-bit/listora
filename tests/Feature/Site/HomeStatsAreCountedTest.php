<?php

namespace Tests\Feature\Site;

use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The hero figures are measurements, not marketing.
 *
 * They used to read "1,840 Resorts represented" and "100% Ownership verified".
 * Nothing produced the first number, and the second stated a policy as though
 * it were a count. Both are representations to whoever reads the page - a
 * visitor deciding whether to trust the site, or an underwriter assessing it -
 * so each one has to move when the catalogue moves, including downwards.
 */
class HomeStatsAreCountedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_figures_follow_the_catalogue(): void
    {
        Listing::factory()->count(3)->create([
            'kind' => Listing::KIND_HOME,
            'region' => 'Florida',
            'is_verified' => true,
        ]);

        Listing::factory()->create([
            'kind' => Listing::KIND_HOME,
            'region' => 'Hawaii',
            'is_verified' => true,
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('>4</span><span class="l">Live listings', $html);
        $this->assertStringContainsString('>2</span><span class="l">Destinations', $html);
        $this->assertStringContainsString('>100%</span><span class="l">Ownership verified', $html);
    }

    /**
     * The one that matters: an unverified listing must pull the percentage
     * down. A figure that only ever reads 100% is a slogan wearing a number.
     */
    public function test_an_unverified_listing_lowers_the_verified_figure(): void
    {
        Listing::factory()->count(3)->create(['kind' => Listing::KIND_HOME, 'is_verified' => true]);
        Listing::factory()->create(['kind' => Listing::KIND_HOME, 'is_verified' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('75%', false)
            ->assertDontSee('>100%</span><span class="l">Ownership verified', false);
    }

    public function test_no_invented_figure_survives(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('1,840', $html);
        $this->assertStringNotContainsString('Resorts represented', $html);
    }
}
