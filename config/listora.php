<?php

return [

    'brand' => [
        'name'    => 'Listora',
        'tagline' => 'Where owners and travelers meet directly.',
        'domain'  => 'listora1.com',

        // The one published address. Every page, the footer, and the Help
        // centre read from here — so changing it is a one-line change rather
        // than a hunt through templates.
        'email'   => 'help@listora1.com',

        /*
        | PLACEHOLDER. (800) 555-0142 is a reserved fictional number and
        | reaches nobody. It is published because the design expects a phone
        | line; swap it for the real one before launch, or set it to null and
        | the Help page will simply omit the phone row rather than print a
        | number that does not answer.
        */
        'phone'   => '(800) 555-0142',
        'phone_is_placeholder' => true,

        /*
        | Deliberately country-level. Listora has no walk-in office and
        | support runs through the Help centre and email, so publishing a
        | street address would invite visits that cannot be served. Add
        | 'city' and 'region' here when there is a real address to give.
        */
        'location' => [
            'label'   => 'Headquarters',
            'country' => 'United States',
        ],

        // Roughly when a human replies. Shown on the Help page so the
        // expectation is set before someone writes in, not after.
        'response_time' => 'within one business day',
    ],

    /*
    |--------------------------------------------------------------------------
    | Advertising plans
    |--------------------------------------------------------------------------
    | Listora is an advertising marketplace. Owners pay one flat fee for a
    | 12-month listing. No commission is ever taken from the transaction.
    */
    'plans' => [
        'essential' => [
            'name'     => 'Essential',
            'price'    => 89,
            'blurb'    => 'Everything you need to advertise one vacation property.',
            'features' => [
                'Live for 180 days',
                'Ownership verified before publishing',
                'Up to 20 photos',
                'Direct messaging with travelers and buyers',
                'Appears in all search results',
                'Edit your listing any time',
            ],
        ],
        'featured' => [
            'name'      => 'Featured',
            'price'     => 179,
            'popular'   => true,
            'blurb'     => 'Priority placement and the visibility tools that move listings fastest.',
            'features'  => [
                'Everything in Essential',
                'Priority placement above standard listings',
                'Featured badge on every card',
                'Rotating placement on the homepage',
                'Monthly performance report',
                'Included in our weekly traveler email',
            ],
        ],
        'premier' => [
            'name'     => 'Premier',
            'price'    => 349,
            'blurb'    => 'Top-of-results placement plus a listing our team writes and shoots for you.',
            'features' => [
                'Everything in Featured',
                'Top-of-results placement in your region',
                'Professional listing copy written for you',
                'Photo editing and sequencing by our team',
                'Dedicated listing specialist',
                'Renew free if it does not move in 180 days',
            ],
        ],
    ],

    'regions' => [
        'Hawaii', 'Caribbean', 'Mexico', 'Florida', 'Southeast Coast',
        'Mountain West', 'Southwest Desert', 'California Coast',
        'Lakes & Midwest', 'Europe',
    ],

    'amenities' => [
        'Oceanfront', 'Ocean view', 'Private pool', 'Shared pool', 'Hot tub',
        'Full kitchen', 'Washer & dryer', 'Air conditioning', 'Wi-Fi',
        'Private balcony', 'Beach access', 'Ski-in / ski-out', 'Golf on site',
        'Fitness center', 'Covered parking', 'Pet friendly', 'Elevator',
        'Grill', 'Fireplace', 'Concierge',
    ],

    'clubs' => [
        'Coral Cay Club', 'Summit Ridge Collection', 'Blue Harbour Residences',
        'Palmetto Shores Club', 'Aurora Vacation Collection', 'Sandpiper Club',
    ],

];
