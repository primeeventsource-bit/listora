<?php

namespace Database\Factories;

use App\Enums\ListingStatus;
use App\Enums\PlanTier;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    public function definition(): array
    {
        $title = $this->faker->streetName().' '.$this->faker->randomElement(['Villa', 'Residence', 'Retreat']);

        return [
            'reference' => 'LST-'.strtoupper(Str::random(6)),
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),

            'kind' => Listing::KIND_HOME,
            'mode' => 'rent',
            'title' => $title,
            'headline' => $this->faker->sentence(),
            'description' => $this->faker->paragraphs(3, true),

            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'country' => 'United States',
            'region' => $this->faker->randomElement(['Hawaii', 'Caribbean', 'Florida', 'Mountain West']),

            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->randomElement([1, 1.5, 2, 2.5, 3]),
            'sleeps' => $this->faker->numberBetween(2, 10),

            'price' => $this->faker->numberBetween(1_200, 90_000),
            'price_unit' => 'total',
            'currency' => 'USD',

            'plan' => PlanTier::Essential,
            'is_featured' => false,
            'is_verified' => true,
            'owner_name' => $this->faker->name(),
            'amenities' => ['Wi-Fi', 'Full kitchen'],
            'photos' => ['https://images.unsplash.com/photo-1502672260266-1c1ef2d93688'],

            // Live by default: both axes are required for a listing to be
            // public, so a factory that set only one would quietly produce
            // rows that never appear in browse.
            'status' => ListingStatus::Active,
            'verified_at' => now()->subDays(3),
            'published_at' => now()->subDays(2),
            'expires_at' => now()->addMonths(12),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ListingStatus::Draft,
            'verified_at' => null,
            'published_at' => null,
            'expires_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => ListingStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);
    }
}
