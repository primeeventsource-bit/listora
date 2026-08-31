<?php

namespace Tests\Feature\Site;

use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The advertiser dashboard.
 *
 * The screen is a tenant boundary as much as a design: everything on it is
 * scoped to listings the viewer owns. A panel that quietly widened its query
 * would leak one advertiser's demand to another and look completely normal
 * doing it, so the isolation is asserted rather than assumed.
 *
 * Every test here gives the user a listing first. DashboardController::show()
 * picks the dashboard from the data rather than the role column, so an Owner
 * with nothing advertised is shown the traveler dashboard instead — this view
 * is only reachable once something exists.
 */
class MemberDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function advertiser(string $email = 'owner@listora1.test'): User
    {
        return User::factory()->create([
            'role' => UserRole::Owner,
            'email' => $email,
            'email_verified_at' => now(),
        ]);
    }

    public function test_an_advertiser_sees_their_own_listing_and_its_program(): void
    {
        $advertiser = $this->advertiser();

        $listing = Listing::factory()->create([
            'owner_id' => $advertiser->id,
            'title' => 'Kaanapali Ocean View Week 12',
        ]);

        $this->actingAs($advertiser)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Kaanapali Ocean View Week 12')
            // The program panel names what they are paying for, rather than
            // leaving them to infer it from a status pill.
            ->assertSee('Advertising program')
            ->assertSee($listing->reference);
    }

    public function test_one_advertiser_never_sees_another_advertisers_listing(): void
    {
        $mine = $this->advertiser('mine@listora1.test');
        $theirs = $this->advertiser('theirs@listora1.test');

        Listing::factory()->create([
            'owner_id' => $mine->id,
            'title' => 'My Own Property',
        ]);

        Listing::factory()->create([
            'owner_id' => $theirs->id,
            'title' => 'Somebody Elses Property',
        ]);

        $this->actingAs($mine)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('My Own Property')
            ->assertDontSee('Somebody Elses Property');
    }

    /**
     * The member area must not render inside the marketing layout. An
     * advertiser managing paid listings should not be shown the public header
     * that sells plans to strangers, and the console must never be indexed.
     */
    public function test_the_member_dashboard_uses_the_member_shell(): void
    {
        $advertiser = $this->advertiser();

        Listing::factory()->create(['owner_id' => $advertiser->id]);

        $html = $this->actingAs($advertiser)
            ->get('/dashboard')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('css/console.css', $html);
        $this->assertStringNotContainsString('css/listora.css', $html);
        $this->assertStringContainsString('noindex', $html);
    }
}
