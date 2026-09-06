<?php

namespace Tests\Feature\Site;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The advertiser has one screen.
 *
 * Performance was a second page behind its own nav item, which split one
 * question - is my advertising running, and is it doing anything - across two
 * clicks. This pins the merge: the dashboard carries both halves, and the
 * navigation offers no second door to the same room.
 */
class OwnerDashboardIsOnePageTest extends TestCase
{
    use RefreshDatabase;

    private function advertiser(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Owner,
            'email_verified_at' => now(),
        ]);

        Listing::factory()->create(['owner_id' => $user->id]);

        return $user;
    }

    public function test_the_dashboard_carries_the_program_and_the_traffic(): void
    {
        $html = $this->actingAs($this->advertiser())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        // What is running.
        $this->assertStringContainsString('Advertising program', $html);
        $this->assertStringContainsString('Term ends', $html);

        // What it did.
        $this->assertStringContainsString('Traffic and engagement', $html);
        $this->assertStringContainsString('Advertisement views', $html);
        $this->assertStringContainsString('Where your traffic came from', $html);
        $this->assertStringContainsString('Engagement', $html);

        // And the filter that governs the second half.
        $this->assertStringContainsString('name="range"', $html);
    }

    public function test_the_navigation_no_longer_offers_a_performance_page(): void
    {
        $html = $this->actingAs($this->advertiser())
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('>Performance<', $html);
        $this->assertStringNotContainsString(route('owner.performance'), $html);
    }

    /**
     * The period filter must govern only the traffic half. If it reached the
     * program table, narrowing to "today" would empty it and read as though
     * the advertising had stopped.
     */
    public function test_narrowing_the_period_does_not_hide_the_listings(): void
    {
        $this->actingAs($this->advertiser())
            ->get('/dashboard?range=today')
            ->assertOk()
            ->assertSee('Advertising program')
            ->assertSee('Term ends');
    }
}
