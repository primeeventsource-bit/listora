<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listora is an advertising platform, and the people who browse it are
 * visitors.
 *
 * Both words carry weight beyond style. A marketplace is understood to sit
 * between two parties and facilitate the transaction between them, which is
 * the opposite of what this site does and the wrong thing to tell a payment
 * processor reading the page. And the site sells advertising to owners - the
 * people reading a listing are visiting it, not booking travel through it.
 *
 * Swept across pages rather than checked on one, because the same sentence
 * appears on the How It Works page, in the FAQ and in the help centre, and
 * fixing the copy on the page somebody happened to screenshot is how the
 * other three survive.
 */
class PlatformWordingTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        '/',
        '/how-it-works',
        '/about',
        '/pricing',
        '/list-your-property',
        '/property-information',
        '/apps',
    ];

    public function test_no_page_calls_listora_a_marketplace(): void
    {
        $this->assertNoneSay(['marketplace', 'Marketplace']);
    }

    /**
     * Legal documents are excluded. They use "marketplace" as a settled term
     * in a version-tracked document, so changing it is a new version of that
     * document rather than a copy edit, and belongs to whoever owns the
     * wording.
     */
    public function test_visitors_rather_than_travelers(): void
    {
        $this->assertNoneSay(['traveler', 'Traveler', 'travelers', 'Travelers']);
    }

    /** @param string[] $words */
    private function assertNoneSay(array $words): void
    {
        $found = [];

        foreach (self::PAGES as $page) {
            $html = $this->get($page)->assertOk()->getContent();

            foreach ($words as $word) {
                if (str_contains($html, $word)) {
                    $found[] = "{$page} says \"{$word}\"";
                }
            }
        }

        $this->assertSame([], array_unique($found), implode(PHP_EOL, array_unique($found)));
    }
}
