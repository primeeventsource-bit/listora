<?php

namespace App\Console\Commands;

use App\Enums\ListingStatus;
use App\Enums\PlanTier;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo advertisers with live listings.
 *
 * Exists because the seeded listings have no owner_id, and an ownerless
 * listing has no member advertising number - so no /ad/{member}/{listing}
 * URL, no advertiser to attribute traffic to, and a performance page that
 * shows zeros however much traffic arrives. This makes the whole chain
 * visible end to end with data that is honestly labelled as demonstration.
 *
 * Explicit data rather than factories. fakerphp/faker is require-dev and
 * production installs --no-dev, so a factory-based command would work
 * everywhere except the environment this is for.
 *
 * Five listings across three advertisers rather than one each: the brief's
 * numbering has several listings hanging off one member's ad number, and a
 * one-to-one set would never exercise it.
 *
 * Idempotent. Emails are deterministic, so running it twice updates rather
 * than duplicating - and every address is at .example, a reserved TLD that
 * cannot receive mail, so no demo account can ever be mistaken for a real
 * advertiser or be written to.
 */
class DemoAdvertisersCommand extends Command
{
    protected $signature = 'listora:demo-advertisers
        {--password= : Sign-in password for the demo accounts. Without it they cannot sign in.}
        {--remove : Delete the demo advertisers and their listings instead}';

    protected $description = 'Create demo advertiser accounts with live listings, so the advertising chain can be seen working';

    /** Reserved TLD (RFC 2606). These addresses can never receive mail. */
    private const DOMAIN = 'listora1.example';

    public function handle(): int
    {
        if ($this->option('remove')) {
            return $this->remove();
        }

        $password = $this->option('password');

        if (! $password) {
            $this->warn('No --password given: the demo accounts will own listings but cannot sign in.');
            $this->line('  Re-run with --password=... to be able to sign in as one and see the member dashboard.');
            $this->newLine();
        }

        $advertisers = [];

        foreach ($this->advertisers() as $key => $spec) {
            $advertisers[$key] = $this->upsertAdvertiser($key, $spec, $password);
        }

        $rows = [];

        foreach ($this->listings() as $spec) {
            $owner = $advertisers[$spec['owner']];
            $listing = $this->upsertListing($spec, $owner);

            $rows[] = [
                $listing->title,
                $owner->name,
                $owner->ad_number,
                $listing->ad_number,
                url("/ad/{$owner->ad_number}/{$listing->ad_number}"),
            ];
        }

        $this->newLine();
        $this->table(['Listing', 'Advertiser', 'Ad number', 'Listing number', 'Public URL'], $rows);
        $this->newLine();
        $this->info(count($rows).' live demo listings across '.count($advertisers).' advertisers.');
        $this->line('  Remove them again with: php artisan listora:demo-advertisers --remove');

        return self::SUCCESS;
    }

    private function remove(): int
    {
        $users = User::query()->where('email', 'like', '%@'.self::DOMAIN)->get();

        if ($users->isEmpty()) {
            $this->info('No demo advertisers found.');

            return self::SUCCESS;
        }

        $listings = Listing::query()->whereIn('owner_id', $users->pluck('id'))->get();

        // Listings first: they reference the users, and leaving orphans behind
        // would put ownerless demo rows back on the site.
        $listingCount = $listings->count();
        Listing::query()->whereIn('id', $listings->pluck('id'))->delete();

        $userCount = $users->count();
        User::query()->whereIn('id', $users->pluck('id'))->delete();

        $this->info("Removed {$listingCount} demo listings and {$userCount} demo advertisers.");

        return self::SUCCESS;
    }

    private function upsertAdvertiser(string $key, array $spec, ?string $password): User
    {
        $email = $key.'@'.self::DOMAIN;

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->fill([
            'name' => $spec['name'],
            'first_name' => $spec['first_name'],
            'last_name' => $spec['last_name'],
            'role' => UserRole::Owner,
        ]);

        $user->email_verified_at ??= now();

        // A random secret when none is given: the account exists and owns
        // listings, but nobody can sign in as it. Better than a blank or
        // shared password sitting on a live site.
        if ($password || ! $user->exists) {
            $user->password = Hash::make($password ?: Str::random(48));
        }

        $user->save();

        return $user;
    }

    private function upsertListing(array $spec, User $owner): Listing
    {
        $listing = Listing::query()->firstOrNew(['slug' => $spec['slug']]);

        $listing->fill($spec['attributes'] + [
            'reference' => $listing->reference ?: 'LST-'.strtoupper(Str::random(6)),
            'owner_id' => $owner->id,
            'owner_name' => $owner->name,
            'currency' => 'USD',
            'country' => 'United States',
            'is_verified' => true,
            'is_featured' => false,
            'verified_at' => now()->subDays(6),

            // Both axes are required for a listing to be public. Setting only
            // one produces a row that never appears in browse and looks like a
            // bug in the listing rather than in the data that made it.
            'status' => ListingStatus::Active,
            'published_at' => now()->subDays(4),
            'expires_at' => now()->addMonths(6),
        ]);

        $listing->save();

        return $listing;
    }

    /** @return array<string, array{name:string, first_name:string, last_name:string}> */
    private function advertisers(): array
    {
        return [
            'demo-marisol' => ['name' => 'Marisol Vega', 'first_name' => 'Marisol', 'last_name' => 'Vega'],
            'demo-terrence' => ['name' => 'Terrence Blake', 'first_name' => 'Terrence', 'last_name' => 'Blake'],
            'demo-anneke' => ['name' => 'Anneke Ruis', 'first_name' => 'Anneke', 'last_name' => 'Ruis'],
        ];
    }

    /**
     * All whole-property rentals, deliberately.
     *
     * Points and vacation-week categories are hidden from the public site
     * while payment underwriting is in progress - see Listing::scopePublished.
     * Demo listings in those categories would be created, be invisible, and
     * look like the command had failed.
     *
     * @return list<array{owner:string, slug:string, attributes:array}>
     */
    private function listings(): array
    {
        return [
            [
                'owner' => 'demo-marisol',
                'slug' => 'demo-kaanapali-oceanfront-residence',
                'attributes' => [
                    'kind' => Listing::KIND_HOME,
                    'mode' => 'rent',
                    'title' => 'Kaanapali Oceanfront Residence',
                    'headline' => 'Top-floor corner home with a full ocean view, steps from the shoreline path.',
                    'description' => "A two-bedroom corner residence on the top floor, looking straight down the Kaanapali shoreline.\n\nSleeps six comfortably, with a full kitchen and a wraparound lanai wide enough to eat on. The beach path starts at the building's north gate.\n\nDates and terms are arranged directly with me — Listora advertises the property and nothing more.",
                    'city' => 'Lahaina',
                    'state' => 'HI',
                    'region' => 'Hawaii',
                    'unit_type' => 'Two bedroom',
                    'bedrooms' => 2,
                    'bathrooms' => 2,
                    'sleeps' => 6,
                    'price' => 4200,
                    'price_unit' => 'total',
                    'plan' => PlanTier::Premier,
                    'amenities' => ['Ocean view', 'Full kitchen', 'Wi-Fi', 'Pool', 'Air conditioning'],
                    'photos' => ['https://images.unsplash.com/photo-1507525428034-b723cf961d3e'],
                ],
            ],
            [
                'owner' => 'demo-marisol',
                'slug' => 'demo-scottsdale-desert-villa',
                'attributes' => [
                    'kind' => Listing::KIND_HOME,
                    'mode' => 'rent',
                    'title' => 'Scottsdale Desert Villa',
                    'headline' => 'Quiet villa in north Scottsdale, walking distance to the golf clubhouse.',
                    'description' => "A one-bedroom villa on a quiet cul-de-sac in north Scottsdale.\n\nSleeps four with a sofa bed, full kitchen, and a private walled patio that catches the evening light. The clubhouse and first tee are a five-minute walk.\n\nHappy to answer questions about the area or the courses nearby.",
                    'city' => 'Scottsdale',
                    'state' => 'AZ',
                    'region' => 'Mountain West',
                    'unit_type' => 'One bedroom',
                    'bedrooms' => 1,
                    'bathrooms' => 1,
                    'sleeps' => 4,
                    'price' => 1850,
                    'price_unit' => 'total',
                    'plan' => PlanTier::Essential,
                    'amenities' => ['Golf access', 'Full kitchen', 'Wi-Fi', 'Private patio'],
                    'photos' => ['https://images.unsplash.com/photo-1449844908441-8829872d2607'],
                ],
            ],
            [
                'owner' => 'demo-terrence',
                'slug' => 'demo-orlando-lakeside-home',
                'attributes' => [
                    'kind' => Listing::KIND_HOME,
                    'mode' => 'rent',
                    'title' => 'Orlando Lakeside Home',
                    'headline' => 'Four-bedroom house on a quiet lake, twenty minutes from the parks.',
                    'description' => "A four-bedroom house backing onto a small lake in a residential neighbourhood south of Orlando.\n\nSleeps eight, with a screened pool, a proper kitchen, and a garage. Far enough out to be quiet in the evening and close enough that the drive to the parks is twenty minutes.\n\nI advertise it here and arrange everything directly.",
                    'city' => 'Orlando',
                    'state' => 'FL',
                    'region' => 'Florida',
                    'unit_type' => 'Four bedroom house',
                    'bedrooms' => 4,
                    'bathrooms' => 3,
                    'sleeps' => 8,
                    'price' => 2400,
                    'price_unit' => 'total',
                    'plan' => PlanTier::Featured,
                    'amenities' => ['Screened pool', 'Lake frontage', 'Full kitchen', 'Wi-Fi', 'Garage'],
                    'photos' => ['https://images.unsplash.com/photo-1520250497591-112f2f40a3f4'],
                ],
            ],
            [
                'owner' => 'demo-terrence',
                'slug' => 'demo-breckenridge-slopeside-chalet',
                'attributes' => [
                    'kind' => Listing::KIND_HOME,
                    'mode' => 'rent',
                    'title' => 'Breckenridge Slopeside Chalet',
                    'headline' => 'Three-bedroom chalet on Peak 9, ski lockers on the ground floor.',
                    'description' => "A three-bedroom chalet on Peak 9, close enough to the lift that boots go on at the door.\n\nSleeps eight across three bedrooms, with a stone fireplace, a hot tub on the back deck, and ski lockers downstairs. Warm in January, which is not true of every place up here.\n\nAsk me anything about access or the mountain itself.",
                    'city' => 'Breckenridge',
                    'state' => 'CO',
                    'region' => 'Mountain West',
                    'unit_type' => 'Three bedroom chalet',
                    'bedrooms' => 3,
                    'bathrooms' => 3,
                    'sleeps' => 8,
                    'price' => 5600,
                    'price_unit' => 'total',
                    'plan' => PlanTier::Premier,
                    'amenities' => ['Slopeside', 'Fireplace', 'Hot tub', 'Wi-Fi', 'Ski lockers'],
                    'photos' => ['https://images.unsplash.com/photo-1551698618-1dfe5d97d256'],
                ],
            ],
            [
                'owner' => 'demo-anneke',
                'slug' => 'demo-algarve-cliffside-apartment',
                'attributes' => [
                    'kind' => Listing::KIND_HOME,
                    'mode' => 'rent',
                    'title' => 'Algarve Cliffside Apartment',
                    'headline' => 'Sea-view apartment above Praia da Marinha, ten minutes from the beach steps.',
                    'description' => "A one-bedroom apartment on the cliff road above Praia da Marinha.\n\nSleeps four, air conditioned, with a small terrace facing the water. The coastal path starts at the door and the steps down to the beach are a ten-minute walk east.\n\nI answer inquiries myself, usually the same day.",
                    'city' => 'Lagoa',
                    'state' => 'Faro',
                    'country' => 'Portugal',
                    'region' => 'Caribbean',
                    'unit_type' => 'One bedroom',
                    'bedrooms' => 1,
                    'bathrooms' => 1,
                    'sleeps' => 4,
                    'price' => 1600,
                    'price_unit' => 'total',
                    'plan' => PlanTier::Essential,
                    'amenities' => ['Sea view', 'Air conditioning', 'Wi-Fi', 'Terrace'],
                    'photos' => ['https://images.unsplash.com/photo-1512917774080-9991f1c4c750'],
                ],
            ],
        ];
    }
}
