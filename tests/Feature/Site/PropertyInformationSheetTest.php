<?php

namespace Tests\Feature\Site;

use App\Enums\DraftStatus;
use App\Enums\UserRole;
use App\Models\FeatureFlag;
use App\Models\ListingDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The short intake sheet at /property-information.
 *
 * The thing most worth pinning is where a submission ends up. The sheet and
 * the wizard describe one business event — an unverified claim of ownership
 * that a specialist has to work — and the moment they stop sharing a queue,
 * one of them starts being the queue nobody checks.
 */
class PropertyInformationSheetTest extends TestCase
{
    use RefreshDatabase;

    private const VALID = [
        'kind' => 'weeks',
        'mode' => 'rent',
        'owner_name' => 'Dana Whitfield',
        'owner_email' => 'dana@example.com',
        'phone' => '555-0143',
        'resort_name' => 'Kaanapali Shores',
        'city' => 'Lahaina',
        'state' => 'HI',
        'description' => 'Week 26, fixed, oceanfront two-bedroom.',
    ];

    public function test_the_sheet_renders(): void
    {
        $this->get('/property-information')
            ->assertOk()
            ->assertSee('Property information sheet')
            ->assertSee('a specialist will contact you', false);
    }

    public function test_a_submission_becomes_a_draft_in_the_existing_review_queue(): void
    {
        $this->post('/property-information', self::VALID)
            ->assertRedirect(route('property-information.create'))
            ->assertSessionHas('sheet_reference');

        $draft = ListingDraft::sole();

        $this->assertSame(ListingDraft::SOURCE_SHEET, $draft->source);
        $this->assertSame(DraftStatus::New, $draft->status);
        $this->assertSame('dana@example.com', $draft->owner_email);
        $this->assertSame('Kaanapali Shores', $draft->resort_name);

        // Same queue the wizard feeds — not a second inbox.
        $this->assertTrue(ListingDraft::open()->whereKey($draft->id)->exists());
    }

    /**
     * The sheet does not ask for a plan, so it must not record one. Letting the
     * column default to 'featured' would show a specialist a choice the owner
     * never made.
     */
    public function test_no_plan_is_invented_for_a_sheet_submission(): void
    {
        $this->post('/property-information', self::VALID);

        $this->assertNull(ListingDraft::sole()->plan);
    }

    public function test_the_visitor_gets_a_quotable_reference(): void
    {
        $response = $this->post('/property-information', self::VALID);

        $reference = ListingDraft::sole()->reference;

        $this->assertStringStartsWith('LST-D-', $reference);

        $response->assertSessionHas('sheet_reference', $reference);
        $this->followRedirects($response)->assertSee($reference);
    }

    public function test_only_the_four_essentials_are_required(): void
    {
        $this->post('/property-information', [])
            ->assertSessionHasErrors(['kind', 'mode', 'owner_name', 'owner_email']);

        // Everything the wizard insists on stays optional here on purpose —
        // the specialist call is what fills those in.
        $this->post('/property-information', [
            'kind' => 'points',
            'mode' => 'own',
            'owner_name' => 'Sam Iyer',
            'owner_email' => 'sam@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, ListingDraft::count());
    }

    public function test_a_bad_email_is_rejected(): void
    {
        $this->post('/property-information', ['owner_email' => 'not-an-email'] + self::VALID)
            ->assertSessionHasErrors('owner_email');

        $this->assertSame(0, ListingDraft::count());
    }

    public function test_a_specialist_sees_the_sheet_and_that_it_came_in_thin(): void
    {
        $this->post('/property-information', self::VALID);

        $admin = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.drafts.index'))
            ->assertOk()
            ->assertSee(ListingDraft::sole()->reference)
            ->assertSee('Information sheet');
    }

    /** Contact details are all this form takes, so it says what happens to them. */
    public function test_it_states_no_payment_and_no_lead_reselling(): void
    {
        $this->get('/property-information')
            ->assertOk()
            ->assertSee('never ask you for card or bank details', false)
            ->assertSee('never resold as a lead', false);
    }

    public function test_the_home_page_step_links_to_it(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('property-information.create'))
            ->assertSee('Property information sheet');
    }

    /** Same flag that gates the wizard — one switch turns off both doors. */
    public function test_it_closes_with_the_listing_wizard_feature_flag(): void
    {
        // The column is `rollout_pct`; there is no `rollout_percentage`, and
        // passing one would have thrown on insert even once the import existed.
        FeatureFlag::query()->updateOrCreate(
            ['key' => 'listing_wizard'],
            ['enabled' => false, 'rollout_pct' => 0],
        );

        $this->post('/property-information', self::VALID)->assertForbidden();

        $this->assertSame(0, ListingDraft::count());
    }
}
