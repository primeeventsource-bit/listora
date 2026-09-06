<?php

namespace Tests\Feature\Site;

use App\Services\Legal\LegalDocumentRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The version a legal page displays must be the version the system records.
 *
 * Each page previously printed its number as literal text in the heading, so
 * bumping a version in the registry left the published document showing the
 * old one. Terms went to v7 while the page still read v6, privacy to v5 while
 * the page read v3.
 *
 * That is not cosmetic. TermsAcceptance records what a user accepted by the
 * registry's label, so the record said v7 and the document in front of them
 * said v6 - and in any dispute about what was agreed, those are two different
 * documents.
 */
class LegalVersionsMatchTest extends TestCase
{
    use RefreshDatabase;

    public static function documents(): array
    {
        return [
            'terms of service' => ['/legal/tos', LegalDocumentRegistry::KIND_TOS],
            'privacy policy' => ['/legal/privacy', LegalDocumentRegistry::KIND_PRIVACY],
            'advertising agreement' => ['/legal/advertising-agreement', LegalDocumentRegistry::KIND_ADVERTISING_AGREEMENT],
        ];
    }

    /**
     * @dataProvider documents
     */
    public function test_the_published_page_shows_the_registered_version(string $url, string $kind): void
    {
        $expected = app(LegalDocumentRegistry::class)->versionLabelFor($kind);

        $this->assertNotNull($expected, "No version registered for {$kind}.");

        $this->get($url)
            ->assertOk()
            ->assertSee("Version {$expected}");
    }

    /**
     * @dataProvider documents
     */
    public function test_no_stale_version_number_is_hard_coded(string $url, string $kind): void
    {
        $current = app(LegalDocumentRegistry::class)->versionLabelFor($kind);
        $html = $this->get($url)->assertOk()->getContent();

        // Any other vN in the version line would mean a second, contradictory
        // claim about which document this is.
        preg_match_all('/Version (v\d+)/', $html, $found);

        $this->assertSame(
            [$current],
            array_values(array_unique($found[1])),
            "{$url} displays a version other than the registered {$current}."
        );
    }
}
