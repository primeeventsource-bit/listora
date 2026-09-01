<?php

namespace Tests\Feature\Site;

use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Making an offer from a listing page.
 *
 * The route, the form request, OfferService, and the owner's accept/decline
 * screens all existed before this - and nothing in the site posted to any of
 * them. The whole feature was reachable only by constructing the request by
 * hand, which meant it was, in practice, not a feature.
 *
 * These tests exist to stop that happening again quietly: the form has to be
 * on the page, and posting it has to produce an offer.
 */
class MakeAnOfferTest extends TestCase
{
    use RefreshDatabase;

    private function advertisedListing(): Listing
    {
        return Listing::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'kind' => Listing::KIND_HOME,
            'mode' => 'own',
            'price' => 49995,
        ]);
    }

    public function test_the_listing_page_offers_a_way_to_make_an_offer(): void
    {
        $listing = $this->advertisedListing();

        $this->get($listing->publicUrl())
            ->assertOk()
            ->assertSee('Want to know more?')
            ->assertSee('For sale by owner')
            // The form must actually point at the route, not merely look like it.
            ->assertSee(route('offers.store', $listing), false);
    }

    /**
     * One form, not two.
     *
     * The listing card used to carry a separate inquiry form above the offer
     * form, which asked the visitor to classify their own message before
     * writing it. Everything posts to offers.store now, and the optional
     * amount is what distinguishes a question from an offer.
     */
    public function test_the_page_carries_one_form_not_a_separate_inquiry_form(): void
    {
        $listing = $this->advertisedListing();

        $this->get($listing->publicUrl())
            ->assertOk()
            ->assertDontSee(route('inquiries.store', $listing), false);
    }

    /** The asking price is prefilled, so the common case is one keystroke. */
    public function test_the_offer_amount_is_prefilled_with_the_asking_price(): void
    {
        $listing = $this->advertisedListing();

        $this->get($listing->publicUrl())
            ->assertOk()
            ->assertSee('value="49995"', false);
    }

    public function test_submitting_an_offer_records_it(): void
    {
        $listing = $this->advertisedListing();

        $this->post(route('offers.store', $listing), [
            'name' => 'Dana Reeve',
            'email' => 'dana@example.test',
            'phone' => '555 0100',
            'offer_amount' => 45000,
            'message' => 'I can move quickly if the figure works for you.',
        ])->assertRedirect();

        $offer = Offer::query()->latest('id')->first();

        $this->assertNotNull($offer, 'The offer should have been recorded.');
        $this->assertSame($listing->id, $offer->listing_id);
        $this->assertSame('dana@example.test', $offer->email);
    }

    /**
     * An offer without a figure is still an offer worth passing on - the
     * amount is nullable in the form request, and the page must not be
     * stricter than the rules it posts to.
     */
    public function test_an_offer_without_an_amount_is_accepted(): void
    {
        $listing = $this->advertisedListing();

        $this->post(route('offers.store', $listing), [
            'name' => 'Dana Reeve',
            'email' => 'dana@example.test',
            'message' => 'Would you consider a longer term at a lower rate?',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(1, Offer::query()->count());
    }

    public function test_an_offer_cannot_be_made_on_a_listing_that_is_not_public(): void
    {
        $listing = Listing::factory()->draft()->create([
            'owner_id' => User::factory()->create()->id,
        ]);

        $this->post(route('offers.store', $listing), [
            'name' => 'Dana Reeve',
            'email' => 'dana@example.test',
            'message' => 'Is this one still going to be advertised?',
        ])->assertNotFound();

        $this->assertSame(0, Offer::query()->count());
    }
}
