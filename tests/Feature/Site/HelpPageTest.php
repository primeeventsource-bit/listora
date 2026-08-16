<?php

namespace Tests\Feature\Site;

use App\Models\ContactMessage;
use App\Models\HelpArticle;
use Database\Seeders\HelpArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Help page replaced the Contact tab.
 *
 * The old tab was a bare `mailto:` — it opened a blank email client, offered
 * no answers on the way, and left no record on our side that anyone had asked.
 * These tests pin the things that made replacing it worthwhile.
 */
class HelpPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_help_page_publishes_the_contact_details(): void
    {
        $this->get('/help')
            ->assertOk()
            ->assertSee('help@listora1.com')
            ->assertSee('Headquarters')
            ->assertSee('United States');
    }

    /**
     * The configured number is a reserved fictional 555 line. Publishing it as
     * though it answers would send people to dead air, so the page has to say
     * so in the same breath.
     */
    public function test_the_placeholder_phone_number_is_labelled_as_not_live(): void
    {
        $this->get('/help')
            ->assertSee(config('listora.brand.phone'))
            ->assertSee('not open yet');
    }

    public function test_the_help_tab_is_in_the_nav_and_the_contact_mailto_is_gone(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString(route('help.index'), $html);
        $this->assertStringNotContainsString(
            'mailto:'.config('listora.brand.email').'">Contact',
            $html,
            'The nav should link to the Help page, not open a blank email.',
        );
    }

    /**
     * /contact was linked from the old nav and footer and may be bookmarked or
     * indexed. A 404 on the page people reach for when they need help is the
     * worst possible dead end.
     */
    public function test_the_old_contact_url_redirects_into_the_help_page(): void
    {
        $this->get('/contact')
            ->assertRedirect('/help#ask')
            ->assertStatus(301);
    }

    public function test_the_ask_a_question_form_persists_and_returns_a_reference(): void
    {
        $response = $this->post('/contact', [
            'first_name' => 'Test',
            'last_name' => 'Visitor',
            'email' => 'visitor@example.com',
            'department' => 'general',
            'subject' => 'Does the help form work',
            'message' => 'Checking that this reaches the team and comes back with a reference.',
        ]);

        $response->assertRedirect(route('help.index').'#ask');
        $response->assertSessionHas('contact_reference');

        $message = ContactMessage::sole();
        $this->assertSame('visitor@example.com', $message->email);
        $this->assertNotEmpty($message->reference);
    }

    public function test_a_short_question_is_rejected_rather_than_silently_accepted(): void
    {
        $this->post('/contact', [
            'first_name' => 'Test',
            'last_name' => 'Visitor',
            'email' => 'visitor@example.com',
            'department' => 'general',
            'subject' => 'Hi',
            'message' => 'too short',
        ])->assertSessionHasErrors('message');

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_seeded_help_articles_render_and_are_searchable(): void
    {
        $this->seed(HelpArticleSeeder::class);

        $this->assertGreaterThan(0, HelpArticle::published()->count());

        $this->get('/help')
            ->assertOk()
            ->assertSee('What ownership verification involves');

        $this->get('/help/ownership-verification')
            ->assertOk()
            ->assertSee('one to two business days');

        // The same JSON endpoint the support assistant's search tool calls, so
        // the page and the assistant cannot drift apart on what they answer.
        $this->getJson('/help/search?q=verification')
            ->assertOk()
            ->assertJsonPath('results.0.title', 'What ownership verification involves');
    }

    public function test_the_help_page_carries_the_assistant_and_not_a_payment_field(): void
    {
        $html = $this->get('/help')->assertOk()->getContent();

        $this->assertStringContainsString('id="chatForm"', $html);
        $this->assertStringContainsString('/api/v1/support/chat', $html);

        // Listora takes no payment anywhere on the site; a card field appearing
        // on the page people trust most would be a genuine problem.
        $this->assertStringNotContainsString('card_number', $html);
        $this->assertStringNotContainsString('cardnumber', strtolower($html));
    }
}
