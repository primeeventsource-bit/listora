<?php

namespace Database\Factories;

use App\Enums\DraftStatus;
use App\Enums\PlanTier;
use App\Models\ListingDraft;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListingDraft>
 */
class ListingDraftFactory extends Factory
{
    protected $model = ListingDraft::class;

    public function definition(): array
    {
        return [
            // `reference` is left to the model's creating hook so factory rows
            // exercise the same generator production uses.
            'kind' => 'home',
            'mode' => 'rent',

            'owner_name' => $this->faker->name(),
            'owner_email' => $this->faker->safeEmail(),
            'phone' => '+1 555 010 0000',

            'property_name' => $this->faker->company().' Resort',
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'region' => 'Florida',

            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(2, true),

            'bedrooms' => 2,
            'sleeps' => 6,
            'price' => 8_500,
            'price_unit' => 'total',

            'plan' => PlanTier::Explorer,
            'status' => DraftStatus::New,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => DraftStatus::Verified,
            'verified_at' => now(),
        ]);
    }
}
