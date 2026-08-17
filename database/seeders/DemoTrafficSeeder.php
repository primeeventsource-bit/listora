<?php

namespace Database\Seeders;

use App\Enums\Surface;
use App\Models\Listing;
use App\Models\PpcVisitor;
use App\Models\TrackingEvent;
use App\Services\GeoIp\CountryCentroids;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Ninety days of plausible traffic, so the reports screen has something to be
 * right or wrong about.
 *
 * The geo, campaign, and click panels all read from `tracking_events` and
 * `ppc_visitors`, which on any environment that has not had real visitors are
 * simply empty — and an analytics screen with no rows tells you nothing about
 * whether it works. This fills them.
 *
 *   php artisan db:seed --class=DemoTrafficSeeder
 *
 * Refuses to run in production. These rows are indistinguishable from real
 * traffic once written — same table, same shape — so seeding them on a live
 * site would quietly corrupt every number the business later reports.
 *
 * NOT idempotent, deliberately. `tracking_events` is append-only: the model
 * throws on UPDATE and the table carries a hash chain, so there is no
 * "re-seed" that could rewrite what is already there. Running twice gives you
 * twice the traffic.
 */
class DemoTrafficSeeder extends Seeder
{
    private const DAYS = 90;

    /**
     * Where the traffic comes from. Weighted so the shape resembles a US
     * vacation-property audience rather than a uniform spread, which would
     * make every geo panel look identically and uselessly flat.
     */
    private const MARKETS = [
        ['US', 'Texas', 'Austin', 34],
        ['US', 'Florida', 'Orlando', 30],
        ['US', 'California', 'San Diego', 26],
        ['US', 'New York', 'New York', 22],
        ['US', 'Illinois', 'Chicago', 16],
        ['CA', 'Ontario', 'Toronto', 14],
        ['CA', 'British Columbia', 'Vancouver', 8],
        ['GB', 'England', 'London', 11],
        ['DE', 'Bavaria', 'Munich', 6],
        ['FR', 'Île-de-France', 'Paris', 5],
        ['MX', 'Jalisco', 'Guadalajara', 7],
        ['AU', 'New South Wales', 'Sydney', 4],
        ['NL', 'North Holland', 'Amsterdam', 3],
        ['ES', 'Madrid', 'Madrid', 3],
        ['BR', 'São Paulo', 'São Paulo', 3],
        ['IE', 'Leinster', 'Dublin', 2],
    ];

    /** Event type => rough share of all events. A funnel, not a flat list. */
    private const EVENT_MIX = [
        'page_view' => 46,
        'listing_view' => 24,
        'search_performed' => 12,
        'help_search' => 5,
        'chat_message_sent' => 4,
        'inquiry_submitted' => 4,
        'signup_subscribed' => 2,
        'offer_submitted' => 2,
        'listing_draft_submitted' => 1,
    ];

    private const CAMPAIGNS = [
        ['google', 'cpc', 'vacation-properties-exact', 'vacation property for rent'],
        ['google', 'cpc', 'vacation-properties-broad', 'own a vacation home'],
        ['google', 'cpc', 'brand', 'listora'],
        ['bing', 'cpc', 'vacation-properties-exact', 'vacation rental by owner'],
        ['facebook', 'paid_social', 'owners-retarget', null],
        ['newsletter', 'email', 'august-owners', null],
    ];

    public function run(): void
    {
        if (App::environment('production')) {
            throw new RuntimeException(
                'DemoTrafficSeeder refuses to run in production: these rows are indistinguishable '
                .'from real traffic once written, and tracking_events is append-only, so they could '
                .'not be cleanly removed afterwards.'
            );
        }

        $listingSlugs = Listing::query()->published()->pluck('slug')->all();
        $visitors = $this->makeVisitors();

        $events = 0;

        foreach (range(self::DAYS, 0) as $daysAgo) {
            $day = Carbon::now()->subDays($daysAgo)->startOfDay();

            foreach (range(1, $this->volumeFor($day)) as $ignored) {
                $this->recordEvent($day, $visitors, $listingSlugs);
                $events++;
            }
        }

        $this->command?->newLine();
        $this->command?->info(sprintf(
            '%s tracking events across %d days, %d attributed visitors, %d markets.',
            number_format($events),
            self::DAYS,
            count($visitors),
            count(self::MARKETS),
        ));
        $this->command?->warn('Demo traffic is written to the same tables as real traffic. Never seed it anywhere public.');
    }

    /**
     * Weekday-heavy with a slow upward trend, so the traffic chart has a shape
     * a person can read rather than a flat band of noise.
     */
    private function volumeFor(Carbon $day): int
    {
        $base = 18 + (int) round((self::DAYS - $day->diffInDays(Carbon::now())) * 0.12);
        $weekend = $day->isWeekend() ? 0.62 : 1.0;

        return max(4, (int) round($base * $weekend * random_int(70, 130) / 100));
    }

    /**
     * Visitors first, so events can be attributed to a stable id and the
     * campaign panel has something to join on. Roughly a third of traffic is
     * paid, which is what makes the attribution panel worth looking at.
     */
    private function makeVisitors(): array
    {
        $visitors = [];

        foreach (range(1, 140) as $i) {
            $visitorId = (string) Str::uuid();
            $visitors[] = $visitorId;

            if ($i % 3 !== 0) {
                continue;
            }

            [$source, $medium, $campaign, $term] = self::CAMPAIGNS[array_rand(self::CAMPAIGNS)];

            PpcVisitor::create([
                'visitor_id' => $visitorId,
                'first_seen_at' => Carbon::now()->subDays(random_int(0, self::DAYS)),
                'landing_url' => 'https://listora1.com/'.['browse', 'browse?kind=home', 'pricing', 'list-your-property'][random_int(0, 3)],
                'utm_source' => $source,
                'utm_medium' => $medium,
                'utm_campaign' => $campaign,
                'utm_term' => $term,
                'utm_content' => null,
                'gclid' => $medium === 'cpc' && $source === 'google' ? Str::upper(Str::random(22)) : null,
                'fbclid' => $source === 'facebook' ? Str::upper(Str::random(20)) : null,
                'referrer' => null,
            ]);
        }

        return $visitors;
    }

    private function recordEvent(Carbon $day, array $visitors, array $listingSlugs): void
    {
        [$country, $region, $city, $weight] = $this->pickMarket();
        $centroid = CountryCentroids::for($country);

        // Jittered off the centroid so a busy country reads as a cluster
        // rather than one pin carrying every visit in the nation.
        $latitude = $centroid ? round($centroid[0] + (random_int(-260, 260) / 100), 4) : null;
        $longitude = $centroid ? round($centroid[1] + (random_int(-380, 380) / 100), 4) : null;

        $eventType = $this->pickEventType();

        $metadata = [
            'geo' => [
                'country' => $country,
                'region' => $region,
                'city' => $city,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'asn' => random_int(7000, 65000),
                'asn_organization' => ['Comcast', 'AT&T', 'Verizon', 'BT', 'Deutsche Telekom', 'Rogers'][random_int(0, 5)],
                // A small slice of anonymised traffic, because a geo panel that
                // never shows any is lying about what the internet looks like.
                'is_vpn' => random_int(1, 100) <= 6,
                'is_tor' => random_int(1, 200) === 1,
                'is_datacenter' => random_int(1, 100) <= 4,
                'is_anonymous' => false,
            ],
            'path' => $this->pathFor($eventType, $listingSlugs),
        ];

        TrackingEvent::create([
            'event_type' => $eventType,
            'visitor_id' => $visitors[array_rand($visitors)],
            'surface' => $this->pickSurface()->value,
            'ip_address' => sprintf('%d.%d.%d.%d', random_int(12, 220), random_int(0, 255), random_int(0, 255), random_int(1, 254)),
            'user_agent' => 'Mozilla/5.0 (demo traffic; DemoTrafficSeeder)',
            'metadata' => $metadata,
            'occurred_at' => $day->copy()->addMinutes(random_int(0, 1439)),
        ]);
    }

    private function pickMarket(): array
    {
        static $total = null;
        $total ??= array_sum(array_column(self::MARKETS, 3));

        $roll = random_int(1, $total);

        foreach (self::MARKETS as $market) {
            $roll -= $market[3];

            if ($roll <= 0) {
                return $market;
            }
        }

        return self::MARKETS[0];
    }

    private function pickEventType(): string
    {
        $roll = random_int(1, array_sum(self::EVENT_MIX));

        foreach (self::EVENT_MIX as $type => $share) {
            $roll -= $share;

            if ($roll <= 0) {
                return $type;
            }
        }

        return 'page_view';
    }

    private function pickSurface(): Surface
    {
        return match (true) {
            random_int(1, 100) <= 72 => Surface::Web,
            random_int(1, 100) <= 60 => Surface::AppIos,
            default => Surface::AppAndroid,
        };
    }

    private function pathFor(string $eventType, array $listingSlugs): string
    {
        return match ($eventType) {
            'listing_view' => $listingSlugs ? '/listing/'.$listingSlugs[array_rand($listingSlugs)] : '/browse',
            'search_performed' => '/browse',
            'help_search', 'chat_message_sent' => '/help',
            'signup_subscribed' => '/signup',
            'listing_draft_submitted' => '/list-your-property',
            'inquiry_submitted', 'offer_submitted' => $listingSlugs ? '/listing/'.$listingSlugs[array_rand($listingSlugs)] : '/browse',
            default => ['/', '/browse', '/pricing', '/how-it-works', '/about', '/inventory'][random_int(0, 5)],
        };
    }
}
