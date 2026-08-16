<?php

namespace Tests\Feature\Tracking;

use App\Models\PpcVisitor;
use Database\Seeders\ListoraSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paid-click attribution, captured on the page the click actually lands on.
 *
 * The failure this prevents is a quiet one: Google Ads reports a click, the
 * database reports an inquiry twenty minutes later, and nothing joins the two
 * because the `gclid` was dropped on the landing page. Every number looks
 * healthy in isolation and the campaign cannot be evaluated at all.
 *
 * The middleware only ran once it was registered in bootstrap/app.php — it
 * existed, fully written, for several commits without being on the stack. So
 * these assertions go through real HTTP rather than calling it directly:
 * a unit test would have passed the whole time it was doing nothing.
 */
class LandingAttributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ListoraSeeder::class);
    }

    public function test_a_paid_click_is_recorded_with_its_campaign(): void
    {
        $response = $this->get('/browse?gclid=EAIaIQ-test-click&utm_source=google&utm_medium=cpc&utm_campaign=weeks-q3');

        $response->assertOk();

        $visitor = PpcVisitor::sole();
        $this->assertSame('EAIaIQ-test-click', $visitor->gclid);
        $this->assertSame('google', $visitor->utm_source);
        $this->assertSame('cpc', $visitor->utm_medium);
        $this->assertSame('weeks-q3', $visitor->utm_campaign);
        $this->assertNotNull($visitor->landing_url);
    }

    public function test_the_visitor_is_cookied_so_later_actions_join_back(): void
    {
        $response = $this->get('/browse?gclid=EAIaIQ-test-click');

        $response->assertOk();
        $response->assertCookie('lst_vid');

        // Read raw: `lst_vid` is exempt from cookie encryption so it survives
        // an APP_KEY rotation across its two-year life. See bootstrap/app.php.
        $this->assertSame(
            PpcVisitor::sole()->visitor_id,
            $response->getCookie('lst_vid', false)->getValue(),
            'The cookie must carry the same id the database recorded, or nothing joins.',
        );
    }

    /**
     * An encrypted `lst_vid` would be tied to APP_KEY, so a key rotation would
     * silently re-cookie every returning visitor as new and lose their
     * original campaign credit.
     */
    public function test_the_visitor_cookie_is_readable_without_the_app_key(): void
    {
        $response = $this->get('/browse?gclid=EAIaIQ-test-click');

        $raw = $response->getCookie('lst_vid', false)->getValue();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $raw,
            'lst_vid should be a plain UUID, not ciphertext.',
        );
    }

    /**
     * Cookie-ing someone to learn nothing is not a trade worth making, and it
     * is what would put this behind a consent banner.
     */
    public function test_organic_traffic_is_left_alone(): void
    {
        $response = $this->get('/browse');

        $response->assertOk();
        $response->assertCookieMissing('lst_vid');
        $this->assertSame(0, PpcVisitor::count());
    }

    /**
     * A returning visitor keeps their original source. Overwriting it with the
     * latest click would credit the last campaign for work the first one did.
     */
    public function test_a_returning_visitor_keeps_their_first_touch(): void
    {
        $first = $this->get('/browse?gclid=first-click&utm_campaign=original');
        $visitorId = $first->getCookie('lst_vid', false)->getValue();

        // withUnencryptedCookie, not withCookie: the latter encrypts the value
        // before sending it, and `lst_vid` is exempt from decryption — so the
        // middleware would receive ciphertext and treat it as a new visitor.
        // A real browser sends exactly what was set, which is this.
        $this->withUnencryptedCookie('lst_vid', $visitorId)
            ->get('/browse?gclid=second-click&utm_campaign=later')
            ->assertOk();

        $this->assertSame(1, PpcVisitor::count(), 'A second visit should not create a second visitor.');
        $this->assertSame('original', PpcVisitor::sole()->utm_campaign);
        $this->assertSame('first-click', PpcVisitor::sole()->gclid);
    }

    public function test_attribution_is_captured_across_the_landing_pages_people_actually_click_to(): void
    {
        foreach (['/', '/browse', '/pricing', '/list-your-property'] as $i => $path) {
            $separator = str_contains($path, '?') ? '&' : '?';

            $this->get($path.$separator.'utm_source=meta&utm_campaign=landing-'.$i)
                ->assertOk();
        }

        // Each request arrives with no cookie, so each is a distinct visitor.
        $this->assertSame(4, PpcVisitor::count());
    }

    /** Meta and Microsoft clicks count too, not just Google. */
    public function test_other_ad_networks_are_recognised(): void
    {
        $this->get('/browse?fbclid=fb-test-click')->assertOk();

        $this->assertSame('fb-test-click', PpcVisitor::sole()->fbclid);
    }
}
