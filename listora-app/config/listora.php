<?php

return [

    'brand' => [
        'name'    => 'Listora',
        'tagline' => 'Where owners and travelers meet directly.',
        'email'   => 'hello@listora.com',
        'phone'   => '(800) 555-0142',
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
            'blurb'    => 'Everything you need to advertise one property, points package, or week.',
            'features' => [
                'Live for 12 full months',
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
                'Renew free if it does not move in 12 months',
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
