<?php

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Simple on both sides" — the tab and the four numbered steps it controls.
 *
 * The toggling itself is JavaScript and out of reach here, but the state the
 * server paints is not, and it is the half that has to be right before any
 * script runs: the selected button and the visible step set must agree on
 * first load. If they drift, the page opens showing the owner's steps with the
 * visitor's tab lit, or vice versa.
 */
class HomeStepsSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_side_is_selected_and_accented_on_first_load(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // The button that is on...
        $this->assertMatchesRegularExpression(
            '/<button[^>]*class="on"[^>]*data-side="owner"[^>]*aria-pressed="true"/',
            $html,
        );

        // ...and the step set it controls, carrying the accent.
        $this->assertStringContainsString('class="grid g4 steps steps-owner is-active" id="stepsOwner"', $html);
    }

    public function test_the_visitor_steps_start_hidden_and_unaccented(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="grid g4 steps steps-visitor" id="stepsVisitor" hidden', $html);
        $this->assertStringContainsString('data-side="visitor"', $html);
        $this->assertMatchesRegularExpression('/data-side="visitor"[^>]*aria-pressed="false"/', $html);
    }

    /**
     * The entrance animation must never be painted server-side. A CSS
     * animation beats `.reveal{opacity:0}`, so shipping it on first load would
     * pop the cards into view before they had been scrolled to — JS adds it on
     * click and only then.
     */
    public function test_the_switch_animation_class_is_never_server_rendered(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('is-switched', false);
    }

    /** Both sides number their steps 1-4; neither is a partial list. */
    public function test_both_sides_carry_four_numbered_steps(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match('#id="stepsOwner".*?id="stepsVisitor"#s', $html, $ownerBlock);
        $this->assertSame(4, substr_count($ownerBlock[0], '<span class="num">'));

        preg_match('#id="stepsVisitor".*?</section>#s', $html, $visitorBlock);
        $this->assertSame(4, substr_count($visitorBlock[0], '<span class="num">'));
    }
}
