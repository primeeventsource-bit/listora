<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Resort;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ListoraSeeder extends Seeder
{
    /**
     * Photography pools. Unsplash images are free for commercial use with no
     * attribution required — swap these for your own CDN paths and nothing
     * else in the application needs to change.
     */
    private array $pool = [
        'beach' => [
            'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2',
            'https://images.unsplash.com/photo-1543489822-c49534f3271f',
            'https://images.unsplash.com/photo-1597211833712-5e41faa202ea',
            'https://images.unsplash.com/photo-1622015663319-e97e697503ee',
            'https://images.unsplash.com/photo-1520483601560-389dff434fdf',
            'https://images.unsplash.com/photo-1578439297699-eb414262c2de',
            'https://images.unsplash.com/photo-1504235083346-e6adb2dc5f34',
            'https://images.unsplash.com/photo-1673619978300-c8efd3584c43',
        ],
        'pool' => [
            'https://images.unsplash.com/photo-1713843841925-6af6ed0df472',
            'https://images.unsplash.com/photo-1715158230572-f571ba4952da',
            'https://images.unsplash.com/photo-1719391083606-da1dd6454a68',
            'https://images.unsplash.com/photo-1620898670223-6f7f07d82a3b',
            'https://images.unsplash.com/photo-1605537964076-3cb0ea2ff329',
            'https://images.unsplash.com/photo-1690000437616-371abd30e3a0',
        ],
        'aerial' => [
            'https://images.unsplash.com/photo-1541417904950-b855846fe074',
            'https://images.unsplash.com/photo-1504681869696-d977211a5f4c',
            'https://images.unsplash.com/photo-1533358122925-6eb2658855bb',
            'https://images.unsplash.com/photo-1520277872024-58b40679ddb4',
            'https://images.unsplash.com/photo-1505118380757-91f5f5632de0',
            'https://images.unsplash.com/photo-1529034502960-57f42a966080',
            'https://images.unsplash.com/photo-1506477331477-33d5d8b3dc85',
            'https://images.unsplash.com/photo-1545556124-500dc7c01f2c',
        ],
        'ocean' => [
            'https://images.unsplash.com/photo-1605207693166-394b929e8a1a',
            'https://images.unsplash.com/photo-1562191326-7a067408be55',
            'https://images.unsplash.com/photo-1722480417258-62f2fe681219',
            'https://images.unsplash.com/photo-1619118986411-29b465253365',
            'https://images.unsplash.com/photo-1612900808516-91a13301c52f',
            'https://images.unsplash.com/photo-1618064541372-289bdb6f5b7b',
        ],
        'suite' => [
            'https://images.unsplash.com/photo-1590381105924-c72589b9ef3f',
            'https://images.unsplash.com/photo-1592229506151-845940174bb0',
            'https://images.unsplash.com/photo-1631049552057-403cdb8f0658',
            'https://images.unsplash.com/photo-1560703652-f3fd178338b7',
            'https://images.unsplash.com/photo-1685592437742-3b56edb46b15',
            'https://images.unsplash.com/photo-1731336478850-6bce7235e320',
        ],
        'cabin' => [
            'https://images.unsplash.com/photo-1510798831971-661eb04b3739',
            'https://images.unsplash.com/photo-1570793005386-840846445fed',
            'https://images.unsplash.com/photo-1583878594798-c31409c8ab4a',
            'https://images.unsplash.com/photo-1482192505345-5655af888cc4',
            'https://images.unsplash.com/photo-1551648746-d158bcd704e7',
            'https://images.unsplash.com/photo-1475087542963-13ab5e611954',
            'https://images.unsplash.com/photo-1531057228999-37933ba12c52',
            'https://images.unsplash.com/photo-1506974210756-8e1b8985d348',
        ],
        'ski' => [
            'https://images.unsplash.com/photo-1550503736-c1a2c9033c03',
            'https://images.unsplash.com/photo-1600332303625-c7d287a9f4b0',
            'https://images.unsplash.com/photo-1546593064-053d21199be1',
            'https://images.unsplash.com/photo-1512582246858-a9bbaf61b61a',
            'https://images.unsplash.com/photo-1515767160065-1733c5eb680f',
            'https://images.unsplash.com/photo-1607867283465-d6792016b481',
        ],
        'desert' => [
            'https://images.unsplash.com/photo-1573691256751-1a35eaf5854c',
            'https://images.unsplash.com/photo-1593021976254-d212c85e627e',
            'https://images.unsplash.com/photo-1570743624969-cd5732a05bf3',
            'https://images.unsplash.com/photo-1570743368744-18e922f1b1bd',
            'https://images.unsplash.com/photo-1638398415609-14d9b0fee25e',
            'https://images.unsplash.com/photo-1656533391149-bd764b6ab5f4',
        ],
        'lake' => [
            'https://images.unsplash.com/photo-1586355216449-d5bf3ea54d65',
            'https://images.unsplash.com/photo-1613005353262-ee61a1fafcf4',
            'https://images.unsplash.com/photo-1626667750279-25358d500e82',
            'https://images.unsplash.com/photo-1579043917252-3c0c03e061c3',
            'https://images.unsplash.com/photo-1566065576695-cd6086cacde1',
            'https://images.unsplash.com/photo-1622976292033-c0cbf0b1c761',
        ],
    ];

    private int $ref = 4100;

    public function run(): void
    {
        $resorts = $this->resorts();

        foreach ($this->listings() as $i => $row) {
            $row['reference']    = 'LST-'.(++$this->ref);
            $row['slug']         = Str::slug($row['title']).'-'.strtolower(substr($row['reference'], 4));
            $row['published_at'] = now()->subDays(random_int(1, 90));
            $row['views']        = random_int(180, 4200);
            $row['saves']        = random_int(4, 190);
            $row['resort_id']    = $resorts[$row['resort_name']] ?? null;

            Listing::create($row);
        }
    }

    // ---------------------------------------------------------------- resorts

    private function resorts(): array
    {
        $rows = [
            ['Kaanapali Shores',        'Coral Cay Club',            'Lahaina',       'HI', 'Hawaii'],
            ['Wailea Point Residences', 'Blue Harbour Residences',   'Wailea',        'HI', 'Hawaii'],
            ['Palmetto Dunes Club',     'Palmetto Shores Club',      'Hilton Head',   'SC', 'Southeast Coast'],
            ['Sandpiper Cay',           'Sandpiper Club',            'Marco Island',  'FL', 'Florida'],
            ['Grand Cayman Reef Club',  'Coral Cay Club',            'Seven Mile Beach', null, 'Caribbean'],
            ['Cabo Azul Residences',    'Blue Harbour Residences',   'San José del Cabo', null, 'Mexico'],
            ['Summit Ridge Lodge',      'Summit Ridge Collection',   'Park City',     'UT', 'Mountain West'],
            ['Aspen Creek Club',        'Summit Ridge Collection',   'Avon',          'CO', 'Mountain West'],
            ['Desert Star Resort',      'Aurora Vacation Collection','Scottsdale',    'AZ', 'Southwest Desert'],
            ['Coronado Bay Club',       'Blue Harbour Residences',   'Coronado',      'CA', 'California Coast'],
            ['Torch Lake Shores',       'Aurora Vacation Collection','Traverse City', 'MI', 'Lakes & Midwest'],
            ['Algarve Cliffs Club',     'Coral Cay Club',            'Albufeira',     null, 'Europe'],
        ];

        $map = [];

        foreach ($rows as [$name, $club, $city, $state, $region]) {
            $r = Resort::create([
                'name'    => $name,
                'slug'    => Str::slug($name),
                'club'    => $club,
                'city'    => $city,
                'state'   => $state,
                'country' => in_array($region, ['Caribbean', 'Mexico', 'Europe']) ? match ($region) {
                    'Caribbean' => 'Cayman Islands',
                    'Mexico'    => 'Mexico',
                    default     => 'Portugal',
                } : 'United States',
                'region'  => $region,
                'summary' => null,
            ]);
            $map[$name] = $r->id;
        }

        return $map;
    }

    private function pick(string $key, int $n, int $offset = 0): array
    {
        $p = $this->pool[$key];
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = $p[($offset + $i) % count($p)];
        }

        return $out;
    }

    // --------------------------------------------------------------- listings

    private function listings(): array
    {
        return [
            // ---------------------------------------------------- vacation homes
            [
                'kind' => 'home', 'mode' => 'rent', 'plan' => 'premier', 'is_featured' => true,
                'title' => 'Oceanfront Villa on Seven Mile Beach',
                'headline' => 'Wake up eleven steps from the water, with the whole beach still empty.',
                'description' => "We bought this villa in 2009 and have spent every February here since. It sits on the quiet northern end of Seven Mile Beach, far enough from the hotels that mornings belong to you and close enough to walk to dinner.\n\nThe main living space opens completely to the terrace, so the sound of the water carries through the house all day. The kitchen is fully equipped and the primary suite faces the sunset. We leave snorkel gear, beach chairs, and a cooler in the storage closet for guests.\n\nWe handle every booking ourselves and are happy to talk through the island before you commit.",
                'resort_name' => 'Grand Cayman Reef Club', 'club_name' => null,
                'city' => 'Seven Mile Beach', 'state' => null, 'country' => 'Cayman Islands', 'region' => 'Caribbean',
                'unit_type' => '3-Bedroom Beachfront Villa', 'bedrooms' => 3, 'bathrooms' => 3.5, 'sleeps' => 8,
                'price' => 685, 'price_unit' => 'night',
                'owner_name' => 'Marcus Whitfield', 'owner_since' => '2019', 'response_time' => 'within 2 hours',
                'amenities' => ['Oceanfront', 'Private pool', 'Full kitchen', 'Beach access', 'Air conditioning', 'Wi-Fi', 'Washer & dryer', 'Covered parking', 'Grill', 'Concierge'],
                'photos' => $this->pick('beach', 5, 0),
            ],
            [
                'kind' => 'home', 'mode' => 'rent', 'plan' => 'featured', 'is_featured' => true,
                'title' => 'Wailea Point Two-Bedroom with Sunset Lanai',
                'headline' => 'Corner unit, top floor, and the lanai faces exactly where the sun goes down.',
                'description' => "This is our family's place on Maui and we rent the weeks we can't use. It's a corner residence on the top floor, which means cross-breeze through the whole unit and no one above you.\n\nThe lanai is the reason we bought it. From November through March you can watch whales from the chair in the corner without getting up. The grounds have three pools, and the beach path takes about four minutes on foot.\n\nSeven-night minimum. We're flexible on arrival day.",
                'resort_name' => 'Wailea Point Residences', 'club_name' => 'Blue Harbour Residences',
                'city' => 'Wailea', 'state' => 'HI', 'country' => 'United States', 'region' => 'Hawaii',
                'unit_type' => '2-Bedroom Ocean View', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'price' => 4200, 'price_unit' => 'week',
                'owner_name' => 'Diane Okamura', 'owner_since' => '2021', 'response_time' => 'within a day',
                'amenities' => ['Ocean view', 'Shared pool', 'Private balcony', 'Full kitchen', 'Air conditioning', 'Wi-Fi', 'Elevator', 'Fitness center', 'Covered parking'],
                'photos' => $this->pick('ocean', 4, 0),
            ],
            [
                'kind' => 'home', 'mode' => 'rent', 'plan' => 'featured',
                'title' => 'Cliffside House Above the Algarve',
                'headline' => 'A whitewashed house on the cliff road, with the cove staircase in the garden.',
                'description' => "Four bedrooms across two levels, built into the cliff so every room looks out at the Atlantic. The lower terrace has a plunge pool and the upper one has the long table where everyone ends up.\n\nThere is a private staircase from the garden down to a cove that almost nobody uses. Albufeira is fifteen minutes by car, Faro airport about forty.\n\nWe live in Lisbon and have a caretaker on site who meets every guest.",
                'resort_name' => 'Algarve Cliffs Club', 'club_name' => null,
                'city' => 'Albufeira', 'state' => null, 'country' => 'Portugal', 'region' => 'Europe',
                'unit_type' => '4-Bedroom Cliffside House', 'bedrooms' => 4, 'bathrooms' => 3, 'sleeps' => 9,
                'price' => 520, 'price_unit' => 'night',
                'owner_name' => 'Inês Carvalho', 'owner_since' => '2022', 'response_time' => 'within 4 hours',
                'amenities' => ['Ocean view', 'Private pool', 'Beach access', 'Full kitchen', 'Wi-Fi', 'Washer & dryer', 'Grill', 'Air conditioning'],
                'photos' => $this->pick('aerial', 4, 2),
            ],
            [
                'kind' => 'home', 'mode' => 'own', 'plan' => 'premier', 'is_featured' => true,
                'title' => 'Coronado Bay Residence, Bayfront Corner',
                'headline' => 'Deeded bayfront residence with the bridge and the skyline out the window.',
                'description' => "We are selling our residence at Coronado Bay after eleven years. It is a deeded corner unit on the seventh floor with unobstructed bay views and a slip available through the marina waitlist.\n\nThe building completed a full exterior renovation in 2024, already paid for and assessed. HOA covers water, insurance, and the grounds.\n\nWe are happy to share the last three years of statements with any serious buyer.",
                'resort_name' => 'Coronado Bay Club', 'club_name' => 'Blue Harbour Residences',
                'city' => 'Coronado', 'state' => 'CA', 'country' => 'United States', 'region' => 'California Coast',
                'unit_type' => '2-Bedroom Bayfront Residence', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 5,
                'price' => 1495000, 'price_unit' => 'total', 'maintenance_fee' => 1180,
                'owner_name' => 'Gregory Aldana', 'owner_since' => '2018', 'response_time' => 'within a day',
                'amenities' => ['Ocean view', 'Shared pool', 'Fitness center', 'Elevator', 'Covered parking', 'Concierge', 'Private balcony', 'Air conditioning'],
                'photos' => $this->pick('suite', 4, 0),
            ],
            [
                'kind' => 'home', 'mode' => 'rent', 'plan' => 'essential',
                'title' => 'Torch Lake Cottage with Private Dock',
                'headline' => 'The water here really is that color. The dock is yours for the week.',
                'description' => "Our family cottage on the east arm of Torch Lake, in the family since 1974 and updated properly in 2023. Three bedrooms, a screened porch that gets used more than the living room, and 90 feet of private frontage.\n\nThe dock holds two boats and there's a sandbar a short swim out where everyone congregates on Saturdays. Traverse City is thirty minutes.\n\nSaturday to Saturday, June through September.",
                'resort_name' => 'Torch Lake Shores', 'club_name' => null,
                'city' => 'Traverse City', 'state' => 'MI', 'country' => 'United States', 'region' => 'Lakes & Midwest',
                'unit_type' => '3-Bedroom Lakefront Cottage', 'bedrooms' => 3, 'bathrooms' => 2, 'sleeps' => 7,
                'price' => 3100, 'price_unit' => 'week',
                'owner_name' => 'Patricia Lindqvist', 'owner_since' => '2020', 'response_time' => 'within 2 days',
                'amenities' => ['Full kitchen', 'Washer & dryer', 'Wi-Fi', 'Grill', 'Fireplace', 'Pet friendly', 'Beach access'],
                'photos' => $this->pick('lake', 4, 0),
            ],
            [
                'kind' => 'home', 'mode' => 'rent', 'plan' => 'featured',
                'title' => 'Ski-In Cabin at Summit Ridge',
                'headline' => 'Boots on at the door, first chair in nine minutes.',
                'description' => "A proper timber cabin twenty yards off the Meadowlark run. Four bedrooms, a boot room with heated racks, and a stone fireplace that heats the whole main level.\n\nWe've owned it since 2017 and rent roughly twelve weeks a year. Summer is quieter and honestly better value — the lift runs for mountain biking and the deck gets sun until nine.\n\nThree-night minimum, seven nights over holiday weeks.",
                'resort_name' => 'Summit Ridge Lodge', 'club_name' => 'Summit Ridge Collection',
                'city' => 'Park City', 'state' => 'UT', 'country' => 'United States', 'region' => 'Mountain West',
                'unit_type' => '4-Bedroom Slopeside Cabin', 'bedrooms' => 4, 'bathrooms' => 3.5, 'sleeps' => 10,
                'price' => 940, 'price_unit' => 'night',
                'owner_name' => 'Tom Bergstrom', 'owner_since' => '2019', 'response_time' => 'within 3 hours',
                'amenities' => ['Ski-in / ski-out', 'Hot tub', 'Fireplace', 'Full kitchen', 'Washer & dryer', 'Wi-Fi', 'Covered parking', 'Pet friendly'],
                'photos' => $this->pick('cabin', 5, 0),
            ],
            [
                'kind' => 'home', 'mode' => 'rent', 'plan' => 'essential',
                'title' => 'Desert Star Casita with Mountain Views',
                'headline' => 'Quiet end of the resort, McDowell range filling the back window.',
                'description' => "A one-bedroom casita on the north edge of Desert Star, where the buildings stop and the preserve begins. Private patio with a plunge pool and an outdoor shower.\n\nWe use it in spring and rent the rest of the year. January through April books quickly; summer rates are half and the pool is genuinely usable at night.\n\nGolf is on site and the trailhead is a five-minute walk.",
                'resort_name' => 'Desert Star Resort', 'club_name' => 'Aurora Vacation Collection',
                'city' => 'Scottsdale', 'state' => 'AZ', 'country' => 'United States', 'region' => 'Southwest Desert',
                'unit_type' => '1-Bedroom Casita', 'bedrooms' => 1, 'bathrooms' => 1.5, 'sleeps' => 4,
                'price' => 295, 'price_unit' => 'night',
                'owner_name' => 'Sylvia Ruiz', 'owner_since' => '2023', 'response_time' => 'within a day',
                'amenities' => ['Private pool', 'Golf on site', 'Air conditioning', 'Full kitchen', 'Wi-Fi', 'Covered parking', 'Fitness center'],
                'photos' => $this->pick('desert', 4, 0),
            ],
            [
                'kind' => 'home', 'mode' => 'own', 'plan' => 'featured',
                'title' => 'Marco Island Gulf-Front Condominium',
                'headline' => 'Eighth floor, due west, nothing between the balcony and the Gulf.',
                'description' => "Selling our Gulf-front residence at Sandpiper Cay. Two bedrooms, two baths, renovated in 2022 with impact glass throughout and a kitchen that opens to the living space.\n\nThe building is one of only four on this stretch with direct beach access rather than a road crossing. Covered parking space conveys.\n\nWe are motivated but not distressed — we simply spend our winters in Portugal now.",
                'resort_name' => 'Sandpiper Cay', 'club_name' => 'Sandpiper Club',
                'city' => 'Marco Island', 'state' => 'FL', 'country' => 'United States', 'region' => 'Florida',
                'unit_type' => '2-Bedroom Gulf-Front', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'price' => 879000, 'price_unit' => 'total', 'maintenance_fee' => 940,
                'owner_name' => 'Howard Klein', 'owner_since' => '2017', 'response_time' => 'within a day',
                'amenities' => ['Oceanfront', 'Beach access', 'Shared pool', 'Elevator', 'Covered parking', 'Fitness center', 'Private balcony', 'Air conditioning'],
                'photos' => $this->pick('ocean', 4, 3),
            ],

            // ----------------------------------------------------- club points
            [
                'kind' => 'points', 'mode' => 'rent', 'plan' => 'premier', 'is_featured' => true,
                'title' => '3,750 Coral Cay Club Points for 2027 Use',
                'headline' => 'Enough for a two-bedroom in high season, or three shorter stays across the year.',
                'description' => "I have 3,750 Coral Cay Club points sitting in my 2027 balance that I will not be able to use. Rather than let them expire I would rather someone get a real trip out of them.\n\nWhat this actually buys: a two-bedroom oceanfront for seven nights at Kaanapali Shores in shoulder season, or a one-bedroom for a full week in Grand Cayman during high season, or three separate long weekends if you would rather spread them out.\n\nI book the reservation in your name directly through the club once we agree on dates. I have done this eleven times through this site and can put you in touch with previous guests.",
                'resort_name' => 'Kaanapali Shores', 'club_name' => 'Coral Cay Club',
                'city' => 'Lahaina', 'state' => 'HI', 'country' => 'United States', 'region' => 'Hawaii',
                'unit_type' => 'Up to 2-Bedroom Oceanfront', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'points' => 3750, 'season' => 'Platinum', 'usage' => 'Annual',
                'available_from' => '2027-01-01', 'available_to' => '2027-12-31',
                'price' => 1.15, 'price_unit' => 'point',
                'owner_name' => 'Ellen Vasquez', 'owner_since' => '2020', 'response_time' => 'within 2 hours',
                'amenities' => ['Oceanfront', 'Shared pool', 'Full kitchen', 'Beach access', 'Wi-Fi', 'Fitness center', 'Concierge'],
                'photos' => $this->pick('beach', 4, 3),
            ],
            [
                'kind' => 'points', 'mode' => 'rent', 'plan' => 'featured',
                'title' => '2,200 Summit Ridge Points — Winter Availability',
                'headline' => 'Ski season points at a mountain club that rarely has open inventory.',
                'description' => "2,200 points in the Summit Ridge Collection, available for winter 2027 dates. Summit Ridge is difficult to book from outside the club, which is the main reason these are worth having.\n\n2,200 gets you a two-bedroom for five nights in January or a one-bedroom for a full week. Park City and Avon are both in the collection.\n\nI reserve in your name at the eleven-month window, so the earlier we talk the better the inventory.",
                'resort_name' => 'Summit Ridge Lodge', 'club_name' => 'Summit Ridge Collection',
                'city' => 'Park City', 'state' => 'UT', 'country' => 'United States', 'region' => 'Mountain West',
                'unit_type' => 'Up to 2-Bedroom Residence', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'points' => 2200, 'season' => 'Prime Winter', 'usage' => 'Annual',
                'available_from' => '2026-12-01', 'available_to' => '2027-04-15',
                'price' => 1.4, 'price_unit' => 'point',
                'owner_name' => 'Nathan Cole', 'owner_since' => '2021', 'response_time' => 'within a day',
                'amenities' => ['Ski-in / ski-out', 'Hot tub', 'Fireplace', 'Fitness center', 'Covered parking', 'Wi-Fi', 'Concierge'],
                'photos' => $this->pick('ski', 4, 0),
            ],
            [
                'kind' => 'points', 'mode' => 'own', 'plan' => 'featured', 'is_featured' => true,
                'title' => '5,000 Annual Blue Harbour Points — Full Transfer',
                'headline' => 'A complete membership transfer, deeded at Wailea Point, annual allocation.',
                'description' => "Transferring my full Blue Harbour membership: 5,000 points annually, deeded at Wailea Point, with no loan against it and all dues current through 2027.\n\n5,000 points a year comfortably covers two weeks in a two-bedroom at most collection properties, or one week in a three-bedroom during high season. The collection includes Wailea, Coronado, and Cabo Azul.\n\nAnnual dues are $2,340. I will cover the club's transfer fee at closing. Happy to walk any serious buyer through the last five years of statements.",
                'resort_name' => 'Wailea Point Residences', 'club_name' => 'Blue Harbour Residences',
                'city' => 'Wailea', 'state' => 'HI', 'country' => 'United States', 'region' => 'Hawaii',
                'unit_type' => 'Up to 3-Bedroom Ocean View', 'bedrooms' => 3, 'bathrooms' => 3, 'sleeps' => 8,
                'points' => 5000, 'season' => 'Platinum', 'usage' => 'Annual',
                'price' => 38500, 'price_unit' => 'total', 'maintenance_fee' => 2340,
                'owner_name' => 'Rebecca Tran', 'owner_since' => '2016', 'response_time' => 'within 4 hours',
                'amenities' => ['Ocean view', 'Shared pool', 'Private balcony', 'Fitness center', 'Concierge', 'Elevator', 'Beach access'],
                'photos' => $this->pick('pool', 4, 0),
            ],
            [
                'kind' => 'points', 'mode' => 'rent', 'plan' => 'essential',
                'title' => '1,500 Aurora Points, Expiring December',
                'headline' => 'Priced to move before they expire — good for a long weekend anywhere in the collection.',
                'description' => "1,500 Aurora Vacation Collection points that expire at the end of December and will otherwise be lost. I would rather they went to someone than to nothing.\n\n1,500 covers a one-bedroom for four nights at Desert Star or Torch Lake Shores, or three nights in a two-bedroom. Not enough for Hawaii inventory.\n\nPriced well below what I normally ask because of the deadline. I can book as late as three weeks out subject to availability.",
                'resort_name' => 'Desert Star Resort', 'club_name' => 'Aurora Vacation Collection',
                'city' => 'Scottsdale', 'state' => 'AZ', 'country' => 'United States', 'region' => 'Southwest Desert',
                'unit_type' => 'Up to 2-Bedroom Casita', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'points' => 1500, 'season' => 'Gold', 'usage' => 'Annual',
                'available_to' => '2026-12-31',
                'price' => 0.78, 'price_unit' => 'point',
                'owner_name' => 'Curtis Nwosu', 'owner_since' => '2024', 'response_time' => 'within 2 days',
                'amenities' => ['Private pool', 'Golf on site', 'Air conditioning', 'Full kitchen', 'Fitness center', 'Wi-Fi'],
                'photos' => $this->pick('desert', 4, 2),
            ],
            [
                'kind' => 'points', 'mode' => 'rent', 'plan' => 'essential',
                'title' => '2,800 Palmetto Shores Points — Summer Coast',
                'headline' => 'Hilton Head in July without the hotel rate.',
                'description' => "2,800 Palmetto Shores Club points available for the 2027 season. This is a coastal collection — Hilton Head, Kiawah, and two properties on the Georgia coast.\n\n2,800 covers a two-bedroom villa for a full week in July, which is the week everyone actually wants. I book in your name and forward the confirmation directly from the club.\n\nI have rented points through this site every year since 2022.",
                'resort_name' => 'Palmetto Dunes Club', 'club_name' => 'Palmetto Shores Club',
                'city' => 'Hilton Head', 'state' => 'SC', 'country' => 'United States', 'region' => 'Southeast Coast',
                'unit_type' => 'Up to 2-Bedroom Villa', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'points' => 2800, 'season' => 'Prime Summer', 'usage' => 'Annual',
                'available_from' => '2027-05-01', 'available_to' => '2027-09-30',
                'price' => 0.95, 'price_unit' => 'point',
                'owner_name' => 'Denise Marchetti', 'owner_since' => '2022', 'response_time' => 'within a day',
                'amenities' => ['Beach access', 'Shared pool', 'Golf on site', 'Full kitchen', 'Wi-Fi', 'Washer & dryer', 'Pet friendly'],
                'photos' => $this->pick('beach', 4, 5),
            ],
            [
                'kind' => 'points', 'mode' => 'own', 'plan' => 'essential',
                'title' => '2,000 Sandpiper Points, Biennial Even Years',
                'headline' => 'A smaller membership for people who travel every other year.',
                'description' => "Transferring a biennial Sandpiper Club membership: 2,000 points in even-numbered years, deeded at Sandpiper Cay on Marco Island.\n\nDues are billed only in use years, currently $780. This suits someone who takes one substantial trip every other year rather than something annually.\n\nAll dues current. I will pay the transfer fee.",
                'resort_name' => 'Sandpiper Cay', 'club_name' => 'Sandpiper Club',
                'city' => 'Marco Island', 'state' => 'FL', 'country' => 'United States', 'region' => 'Florida',
                'unit_type' => 'Up to 2-Bedroom Gulf View', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'points' => 2000, 'season' => 'Gold', 'usage' => 'Biennial (even years)',
                'price' => 9400, 'price_unit' => 'total', 'maintenance_fee' => 780,
                'owner_name' => 'Marilyn Foster', 'owner_since' => '2019', 'response_time' => 'within 3 days',
                'amenities' => ['Ocean view', 'Beach access', 'Shared pool', 'Elevator', 'Covered parking', 'Fitness center'],
                'photos' => $this->pick('aerial', 4, 5),
            ],

            // ---------------------------------------------------- resort weeks
            [
                'kind' => 'weeks', 'mode' => 'rent', 'plan' => 'premier', 'is_featured' => true,
                'title' => 'Week 26 Oceanfront at Kaanapali Shores',
                'headline' => 'Fixed week 26 — the last week of June, oceanfront, same unit every year.',
                'description' => "Our fixed week 26 at Kaanapali Shores, unit 812. Late June on Maui: dry, warm, and before the August crowd arrives.\n\n812 is a two-bedroom oceanfront on the eighth floor at the quiet end of the building. Full kitchen, washer and dryer, and a lanai wide enough to eat on. Fixed week means it is the same unit and the same dates every year, so there is nothing to reserve and nothing to compete for.\n\nWe are renting 2027 only — we plan to use it again in 2028.",
                'resort_name' => 'Kaanapali Shores', 'club_name' => 'Coral Cay Club',
                'city' => 'Lahaina', 'state' => 'HI', 'country' => 'United States', 'region' => 'Hawaii',
                'unit_type' => '2-Bedroom Oceanfront', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'week_number' => 26, 'season' => 'Platinum', 'usage' => 'Fixed week, annual',
                'available_from' => '2027-06-26', 'available_to' => '2027-07-03',
                'price' => 4650, 'price_unit' => 'week',
                'owner_name' => 'James Aldridge', 'owner_since' => '2015', 'response_time' => 'within 2 hours',
                'amenities' => ['Oceanfront', 'Shared pool', 'Full kitchen', 'Washer & dryer', 'Private balcony', 'Beach access', 'Wi-Fi', 'Air conditioning', 'Fitness center'],
                'photos' => $this->pick('ocean', 5, 1),
            ],
            [
                'kind' => 'weeks', 'mode' => 'own', 'plan' => 'featured', 'is_featured' => true,
                'title' => 'Week 8 at Cabo Azul — Deeded, Annual',
                'headline' => 'Late February in Los Cabos, deeded, with dues paid through next year.',
                'description' => "Selling our deeded week 8 at Cabo Azul Residences. Week 8 is the third week of February — high season, reliably dry, and the hardest week to get at this property.\n\nOne-bedroom suite with a full kitchen and a terrace facing the pool and the sea beyond. Annual usage, no loan, dues paid through 2027.\n\nWe have had it for nine years and are only selling because our daughter now lives in Europe and our travel has shifted. Closing through a licensed escrow company of the buyer's choosing.",
                'resort_name' => 'Cabo Azul Residences', 'club_name' => 'Blue Harbour Residences',
                'city' => 'San José del Cabo', 'state' => null, 'country' => 'Mexico', 'region' => 'Mexico',
                'unit_type' => '1-Bedroom Suite', 'bedrooms' => 1, 'bathrooms' => 1, 'sleeps' => 4,
                'week_number' => 8, 'season' => 'Platinum', 'usage' => 'Deeded, annual',
                'price' => 12900, 'price_unit' => 'total', 'maintenance_fee' => 1120,
                'owner_name' => 'Robert Sandoval', 'owner_since' => '2017', 'response_time' => 'within a day',
                'amenities' => ['Ocean view', 'Shared pool', 'Full kitchen', 'Private balcony', 'Air conditioning', 'Wi-Fi', 'Fitness center', 'Concierge'],
                'photos' => $this->pick('pool', 4, 2),
            ],
            [
                'kind' => 'weeks', 'mode' => 'rent', 'plan' => 'featured',
                'title' => 'Week 51 Slopeside at Aspen Creek',
                'headline' => 'Christmas week, three-bedroom, forty yards from the gondola.',
                'description' => "Our week 51 at Aspen Creek Club — the week containing Christmas. Three-bedroom residence, sleeps eight comfortably, and the gondola plaza is a short walk across the snow.\n\nThis is the week we normally keep for ourselves, but we have family obligations in 2027. It does not come up often and it does not sit unrented.\n\nCheck-in Saturday 20 December, check-out Saturday 27 December.",
                'resort_name' => 'Aspen Creek Club', 'club_name' => 'Summit Ridge Collection',
                'city' => 'Avon', 'state' => 'CO', 'country' => 'United States', 'region' => 'Mountain West',
                'unit_type' => '3-Bedroom Slopeside Residence', 'bedrooms' => 3, 'bathrooms' => 3, 'sleeps' => 8,
                'week_number' => 51, 'season' => 'Holiday Prime', 'usage' => 'Floating, annual',
                'available_from' => '2026-12-20', 'available_to' => '2026-12-27',
                'price' => 8900, 'price_unit' => 'week',
                'owner_name' => 'Karen Whitlock', 'owner_since' => '2018', 'response_time' => 'within 3 hours',
                'amenities' => ['Ski-in / ski-out', 'Hot tub', 'Fireplace', 'Full kitchen', 'Washer & dryer', 'Covered parking', 'Wi-Fi', 'Fitness center', 'Concierge'],
                'photos' => $this->pick('ski', 4, 2),
            ],
            [
                'kind' => 'weeks', 'mode' => 'rent', 'plan' => 'essential',
                'title' => 'Floating Autumn Week at Palmetto Dunes',
                'headline' => 'Pick any week in September or October — the coast at its best and its emptiest.',
                'description' => "A floating autumn week at Palmetto Dunes Club, usable any week from the first of September to the end of October.\n\nHilton Head in autumn is the local secret: water still warm, humidity gone, and the bike paths empty. Two-bedroom villa backing onto the third fairway, five minutes by bike to the beach.\n\nTell me your dates and I will confirm with the club within a day.",
                'resort_name' => 'Palmetto Dunes Club', 'club_name' => 'Palmetto Shores Club',
                'city' => 'Hilton Head', 'state' => 'SC', 'country' => 'United States', 'region' => 'Southeast Coast',
                'unit_type' => '2-Bedroom Villa', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'week_number' => 39, 'season' => 'Gold', 'usage' => 'Floating, annual',
                'available_from' => '2027-09-01', 'available_to' => '2027-10-31',
                'price' => 1850, 'price_unit' => 'week',
                'owner_name' => 'Angela Pruitt', 'owner_since' => '2021', 'response_time' => 'within a day',
                'amenities' => ['Golf on site', 'Shared pool', 'Beach access', 'Full kitchen', 'Washer & dryer', 'Wi-Fi', 'Pet friendly', 'Grill'],
                'photos' => $this->pick('beach', 4, 1),
            ],
            [
                'kind' => 'weeks', 'mode' => 'own', 'plan' => 'essential',
                'title' => 'Week 14 at Desert Star — Spring Training Season',
                'headline' => 'Deeded late-March week in Scottsdale, dues current, priced to transfer quickly.',
                'description' => "Deeded week 14 at Desert Star Resort — the last week of March. Perfect desert weather and the same week as spring training, which is why we bought it originally.\n\nOne-bedroom casita with a private patio and plunge pool. Annual usage, deeded, dues of $890 paid through 2027.\n\nWe have moved to the coast and no longer use it. Priced for a clean, quick transfer rather than a long negotiation.",
                'resort_name' => 'Desert Star Resort', 'club_name' => 'Aurora Vacation Collection',
                'city' => 'Scottsdale', 'state' => 'AZ', 'country' => 'United States', 'region' => 'Southwest Desert',
                'unit_type' => '1-Bedroom Casita', 'bedrooms' => 1, 'bathrooms' => 1, 'sleeps' => 4,
                'week_number' => 14, 'season' => 'Prime Spring', 'usage' => 'Deeded, annual',
                'price' => 6200, 'price_unit' => 'total', 'maintenance_fee' => 890,
                'owner_name' => 'Victor Hammond', 'owner_since' => '2020', 'response_time' => 'within 2 days',
                'amenities' => ['Private pool', 'Golf on site', 'Air conditioning', 'Full kitchen', 'Fitness center', 'Covered parking', 'Wi-Fi'],
                'photos' => $this->pick('desert', 4, 4),
            ],
            [
                'kind' => 'weeks', 'mode' => 'rent', 'plan' => 'featured',
                'title' => 'Week 31 on Seven Mile Beach',
                'headline' => 'First week of August, oceanfront two-bedroom, steps from the sand.',
                'description' => "Week 31 at Grand Cayman Reef Club, oceanfront two-bedroom on the third floor. The first week of August — hot, quiet, and the best snorkeling visibility of the year.\n\nThe unit was fully refurbished in 2024. Kitchen, laundry, and a balcony that catches the trade wind all afternoon.\n\nRenting 2027 only. Airport is twenty minutes and the resort arranges transfers.",
                'resort_name' => 'Grand Cayman Reef Club', 'club_name' => 'Coral Cay Club',
                'city' => 'Seven Mile Beach', 'state' => null, 'country' => 'Cayman Islands', 'region' => 'Caribbean',
                'unit_type' => '2-Bedroom Oceanfront', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'week_number' => 31, 'season' => 'Gold', 'usage' => 'Fixed week, annual',
                'available_from' => '2027-07-31', 'available_to' => '2027-08-07',
                'price' => 3400, 'price_unit' => 'week',
                'owner_name' => 'Priya Raghunathan', 'owner_since' => '2022', 'response_time' => 'within 5 hours',
                'amenities' => ['Oceanfront', 'Beach access', 'Shared pool', 'Full kitchen', 'Washer & dryer', 'Private balcony', 'Air conditioning', 'Wi-Fi'],
                'photos' => $this->pick('aerial', 4, 0),
            ],
            [
                'kind' => 'weeks', 'mode' => 'rent', 'plan' => 'essential',
                'title' => 'Week 22 at Torch Lake Shores',
                'headline' => 'Late May on the lake — before the season starts and the prices double.',
                'description' => "Week 22 at Torch Lake Shores, a two-bedroom lakefront unit with a shared dock and swim raft.\n\nLate May in northern Michigan is genuinely lovely and almost nobody is there yet. Water is cold for swimming but the paddleboards live under the deck and the mornings are silent.\n\nSaturday to Saturday. Kayaks and the grill are included.",
                'resort_name' => 'Torch Lake Shores', 'club_name' => 'Aurora Vacation Collection',
                'city' => 'Traverse City', 'state' => 'MI', 'country' => 'United States', 'region' => 'Lakes & Midwest',
                'unit_type' => '2-Bedroom Lakefront', 'bedrooms' => 2, 'bathrooms' => 2, 'sleeps' => 6,
                'week_number' => 22, 'season' => 'Silver', 'usage' => 'Fixed week, annual',
                'available_from' => '2027-05-29', 'available_to' => '2027-06-05',
                'price' => 1250, 'price_unit' => 'week',
                'owner_name' => 'Doug Ferraro', 'owner_since' => '2023', 'response_time' => 'within 2 days',
                'amenities' => ['Full kitchen', 'Washer & dryer', 'Grill', 'Fireplace', 'Wi-Fi', 'Beach access', 'Pet friendly'],
                'photos' => $this->pick('lake', 4, 2),
            ],
            [
                'kind' => 'weeks', 'mode' => 'own', 'plan' => 'featured',
                'title' => 'Week 33 at Algarve Cliffs — Deeded, Sea View',
                'headline' => 'Mid-August on the Portuguese coast, deeded, sea-view one-bedroom.',
                'description' => "Deeded week 33 at Algarve Cliffs Club. Mid-August, sea-view one-bedroom with a terrace above the cove.\n\nThe club is small — thirty-one units — and week 33 rarely changes hands. Annual usage, dues €640, paid through 2027.\n\nSelling because we bought a house nearby. Transfer handled through the club's own conveyancer.",
                'resort_name' => 'Algarve Cliffs Club', 'club_name' => 'Coral Cay Club',
                'city' => 'Albufeira', 'state' => null, 'country' => 'Portugal', 'region' => 'Europe',
                'unit_type' => '1-Bedroom Sea View', 'bedrooms' => 1, 'bathrooms' => 1, 'sleeps' => 4,
                'week_number' => 33, 'season' => 'Platinum', 'usage' => 'Deeded, annual',
                'price' => 15800, 'price_unit' => 'total', 'maintenance_fee' => 690,
                'owner_name' => 'Miguel Duarte', 'owner_since' => '2019', 'response_time' => 'within a day',
                'amenities' => ['Ocean view', 'Shared pool', 'Beach access', 'Full kitchen', 'Private balcony', 'Wi-Fi', 'Air conditioning'],
                'photos' => $this->pick('ocean', 4, 2),
            ],
        ];
    }
}
