<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listora writes the advertisement. The owner supplies the details.
 *
 * The site was ported from a product where owners wrote their own listings,
 * and the claim survived in three places after the home page started saying
 * "We create your property advertisement" - including "Owners write their own
 * descriptions and we don't rewrite them", which is the opposite of what
 * happens now.
 *
 * This matters past tidiness. Who writes the copy on a listing is a statement
 * about where the content came from, made to a visitor deciding whether to
 * trust it, and one page saying the owner wrote it while another says staff
 * did means one of them is misleading whoever reads it.
 */
class AdvertisementAuthorshipTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        '/',
        '/how-it-works',
        '/about',
        '/pricing',
        '/list-your-property',
        '/property-information',
    ];

    public function test_no_page_says_the_owner_writes_the_advertisement(): void
    {
        $claims = [
            'written by its owner',
            'Owners write their own',
            'we don&#039;t rewrite them',
            "we don't rewrite them",
            'lets you write the listing',
            'owner published',
        ];

        $found = [];

        foreach (self::PAGES as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            foreach ($claims as $claim) {
                if (stripos($html, $claim) !== false) {
                    $found[] = "{$page} still says \"{$claim}\"";
                }
            }
        }

        $this->assertSame([], $found, implode(PHP_EOL, $found));
    }

    /** The home page is where the arrangement is stated outright. */
    public function test_the_home_page_says_listora_creates_the_advertisement(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('We create your property advertisement', false);
    }
}
