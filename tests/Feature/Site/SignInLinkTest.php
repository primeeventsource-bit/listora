<?php

namespace Tests\Feature\Site;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The header's way in.
 *
 * The Sign In button pointed at route('listings.index') — it sent people to the
 * browse page, so the site shipped with no working route to /login from any
 * public page. The desktop button also carries `hide-sm`, so a phone had no
 * link at all even once the target was right.
 */
class SignInLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_offered_a_working_sign_in_link(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('login').'"', $html);
        $this->assertStringContainsString('Sign In', $html);
    }

    /** The bug it replaced: the button used to send people to /browse. */
    public function test_the_sign_in_button_does_not_point_at_browse(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '#<a href="'.preg_quote(route('listings.index'), '#').'"[^>]*>\s*Sign In#',
            $html,
        );
    }

    public function test_it_is_reachable_on_a_phone_too(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Once inside the mobile menu, which the desktop button is hidden from.
        preg_match('#<div class="mobile-nav".*?</div>#s', $html, $m);

        $this->assertNotEmpty($m);
        $this->assertStringContainsString(route('login'), $m[0]);
    }

    /** Offering "Sign In" to someone already signed in is the same mistake. */
    public function test_a_signed_in_user_is_offered_their_dashboard_instead(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $html = $this->actingAs($user)->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('dashboard').'"', $html);
        $this->assertStringNotContainsString('>Sign In<', $html);
    }

    public function test_the_login_page_itself_loads(): void
    {
        $this->get('/login')->assertOk();
    }
}
