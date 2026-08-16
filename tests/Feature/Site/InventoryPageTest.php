<?php

namespace Tests\Feature\Site;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The inventory register.
 *
 * Two things make this page worth having separately from /browse, and both are
 * easy to lose to a well-meaning edit: it shows exactly ten, newest first, and
 * it prints a column of prices without implying any of them can be acted on
 * here. The second is the one that would actually mislead somebody about money.
 */
class InventoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_ten_listings_even_when_more_are_published(): void
    {
        Listing::factory()->count(14)->create([
            'status' => ListingStatus::Active->value,
            'published_at' => now()->subDay(),
        ]);

        $html = $this->get('/inventory')->assertOk()->getContent();

        $rows = substr_count($html, '<tr>') - 1; // less the header row

        $this->assertSame(10, $rows, 'The register should show exactly ten rows.');
    }

    /** Newest first — placement is a paid concern and must not order a register. */
    public function test_it_lists_the_most_recently_published_first(): void
    {
        $older = Listing::factory()->create([
            'status' => ListingStatus::Active->value,
            'published_at' => now()->subMonth(),
            'title' => 'Published Earlier',
            // Placement that would win under browse's `recommended` sort, so
            // this fails loudly if that ordering ever leaks in here.
            'is_featured' => true,
            'plan' => 'premier',
        ]);

        $newer = Listing::factory()->create([
            'status' => ListingStatus::Active->value,
            'published_at' => now()->subHour(),
            'title' => 'Published Later',
            'is_featured' => false,
            'plan' => 'essential',
        ]);

        $html = $this->get('/inventory')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, $older->title),
            strpos($html, $newer->title),
            'The newer listing should appear above the older one.',
        );
    }

    public function test_it_only_shows_published_listings(): void
    {
        $live = Listing::factory()->create([
            'status' => ListingStatus::Active->value,
            'published_at' => now(),
            'title' => 'A Live Listing',
        ]);

        $draft = Listing::factory()->create([
            'status' => ListingStatus::Draft->value,
            'published_at' => null,
            'title' => 'Not Published Yet',
        ]);

        $this->get('/inventory')
            ->assertOk()
            ->assertSee($live->title)
            ->assertDontSee($draft->title);
    }

    /**
     * A table of prices reads like a price list you can act on. The page has to
     * say what it is in the same breath, or it tells someone their money or
     * their dates are held here when they are not.
     */
    public function test_it_states_that_listora_only_advertises(): void
    {
        Listing::factory()->create([
            'status' => ListingStatus::Active->value,
            'published_at' => now(),
        ]);

        $this->get('/inventory')
            ->assertOk()
            ->assertSee('Listora advertises these listings and nothing more')
            ->assertDontSee('Book now')
            ->assertDontSee('Reserve');
    }

    /** The industry's legacy term is banned in user-facing copy. */
    public function test_it_never_uses_the_banned_term(): void
    {
        Listing::factory()->count(3)->create([
            'status' => ListingStatus::Active->value,
            'published_at' => now(),
        ]);

        $this->get('/inventory')
            ->assertOk()
            ->assertDontSee('timeshare', escape: false)
            ->assertDontSee('Timeshare', escape: false);
    }

    public function test_it_offers_a_way_forward_when_nothing_is_published(): void
    {
        $this->get('/inventory')
            ->assertOk()
            ->assertSee('Nothing is published yet')
            ->assertSee(route('list.create'));
    }

    public function test_it_is_linked_from_the_footer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('inventory'));
    }
}
