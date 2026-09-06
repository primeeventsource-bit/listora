<?php

namespace Tests\Feature\Advertising;

use App\Enums\AdEventType;
use App\Models\AdEvent;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * What actually reaches the activity log.
 *
 * An audit trail is only worth the screens built on it if the events are
 * genuinely being written, and this is the half that cannot be checked by
 * looking - a missing recorder call produces a log that renders perfectly and
 * is quietly incomplete.
 *
 * The exclusions matter as much as the recording. Double-counting a listing
 * view would inflate the number an advertiser is paying for, and recording
 * redirects and asset requests would bury the pages a person actually read.
 */
class ActivityRecordingTest extends TestCase
{
    use RefreshDatabase;

    private function types(): array
    {
        return AdEvent::pluck('event_type')->map(fn ($t) => $t instanceof AdEventType ? $t->value : $t)->all();
    }

    public function test_a_page_view_is_recorded_for_an_ordinary_page(): void
    {
        $this->get('/pricing')->assertOk();

        $event = AdEvent::sole();

        $this->assertSame(AdEventType::PageView, $event->event_type);
        $this->assertSame('pricing', $event->path);
        $this->assertNotNull($event->occurred_at);
    }

    /**
     * Listing pages record their own event, with the listing attached. A
     * page_view beside it would put every listing view in the table twice and
     * inflate the advertiser's view count.
     */
    public function test_a_listing_page_is_not_recorded_twice(): void
    {
        $listing = Listing::factory()->create();

        $this->get(route('listings.show', $listing))->assertOk();

        $this->assertNotContains(AdEventType::PageView->value, $this->types());
    }

    public function test_a_redirect_records_nothing(): void
    {
        // Signed out, /dashboard redirects to the sign-in page. A page nobody
        // read is not a page view.
        $this->get('/dashboard')->assertRedirect();

        $this->assertNotContains(AdEventType::PageView->value, $this->types());
    }

    public function test_the_health_check_records_nothing(): void
    {
        $this->get('/up');

        $this->assertSame(0, AdEvent::count());
    }

    public function test_a_post_records_nothing_of_its_own(): void
    {
        // Form submissions record their own specific events; a page_view
        // beside them would describe the same action twice, less precisely.
        $this->post('/property-information', [
            'mode' => 'rent',
            'owner_name' => 'Dana Whitfield',
            'owner_email' => 'dana@example.com',
        ]);

        $this->assertNotContains(AdEventType::PageView->value, $this->types());
    }

    // -----------------------------------------------------------------
    // Auth events
    // -----------------------------------------------------------------

    /**
     * AccountCreated has been in the enum since the beginning with nothing
     * writing it, so "created an account" was missing from the one timeline
     * where it explains everything either side of it.
     */
    public function test_creating_an_account_is_recorded(): void
    {
        $this->post('/register', [
            'first_name' => 'Dana',
            'last_name' => 'Whitfield',
            'email' => 'dana@example.com',
            'phone' => '555 0143',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'accept_terms' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertContains(AdEventType::AccountCreated->value, $this->types());

        // Registration signs the new account in, so both belong in the
        // timeline - that sequence is what a session reads as.
        $this->assertContains(AdEventType::SignedIn->value, $this->types());
    }

    public function test_signing_in_and_out_are_recorded(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'correct-horse-battery',
        ]);

        $this->assertContains(AdEventType::SignedIn->value, $this->types());

        $this->actingAs($user)->post('/logout');

        $this->assertContains(AdEventType::SignedOut->value, $this->types());
    }

    /** Each auth event is written once, not once per registered listener. */
    public function test_an_auth_event_is_recorded_exactly_once(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('correct-horse-battery'),
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        $this->assertSame(
            1,
            AdEvent::where('event_type', AdEventType::SignedIn->value)->count(),
        );
    }
}
