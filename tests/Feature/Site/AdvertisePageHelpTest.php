<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Help on the advertise page.
 *
 * The form is a three-step wizard that shows one panel at a time, so anything
 * placed inside a step is invisible to someone stuck on a different one -
 * which is exactly the person who needs it. This block sits outside the form,
 * and these tests exist so a later refactor cannot quietly move it back in.
 */
class AdvertisePageHelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_advertise_page_offers_a_way_to_get_help(): void
    {
        $this->get('/list-your-property')
            ->assertOk()
            ->assertSee('Would you rather we did this with you?')
            ->assertSee(route('property-information.create'), false)
            ->assertSee(route('help.index').'#ask', false)
            ->assertSee(config('listora.brand.email'));
    }

    /**
     * Every destination has to resolve directly. A help block that leads
     * somewhere broken is worse than none, because it is offered at the moment
     * somebody has already given up on doing it themselves.
     *
     * Asserted as 200, not "not an error": route('contact.show') is a 301 to
     * /help#ask, and linking through it would put a redirect in front of
     * someone who is already stuck.
     */
    public function test_every_destination_it_offers_resolves_directly(): void
    {
        foreach ([
            route('property-information.create'),
            route('help.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    /** The ask form is actually on the help page the card points at. */
    public function test_the_help_page_carries_the_ask_form_it_links_to(): void
    {
        $this->get(route('help.index'))
            ->assertOk()
            ->assertSee('id="ask"', false);
    }
}
