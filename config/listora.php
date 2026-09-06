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
    /*
    |--------------------------------------------------------------------------
    | Advertising plans
    |--------------------------------------------------------------------------
    |
    | Display content for /pricing and the home page. Nothing here charges
    | anything - Listora takes no payment on the website - so these are the
    | published figures and promises, kept in one place so the page, the help
    | centre and the plan enum cannot drift apart.
    |
    | Each feature row is [icon, text, strong, note]:
    |   icon    a key in partials/tiers.blade.php's icon table
    |   text    the plain part of the line
    |   strong  the emphasised tail, or null
    |   note    the italic parenthetical, or null
    |
    | Two headings differ from the artwork they were taken from, which read
    | "Everything in Explorer, plus:" on the Explorer card and "Everything in
    | Signature, plus:" on the Signature card. Each card listed itself. They
    | name the plan below them here.
    |
    */
    'plans' => [
        'starter' => [
            'name'     => 'Starter',
            'tagline'  => 'Get listed. Get seen.',
            'badge'    => 'Ideal for beginners',
            'accent'   => 'blue',
            'price'    => 995,
            'heading'  => "What's included:",
            'blurb'    => 'Everything you need to advertise one vacation property.',
            'features' => [
                ['check',     'Property profiles', '1 property', null],
                ['check',     'Search visibility', 'Standard', null],
                ['check',     'Listing presentation', 'Professional', null],
                ['google',    'Google Ads Campaign', null, '1 campaign'],
                ['facebook',  'Facebook & Instagram Ads', null, '1 campaign set'],
                ['seo',       'Basic SEO & Metadata Optimization', null, null],
                ['mail',      'Standard Email Marketing', null, '1 email blast/month'],
                ['check',     'Inquiry notifications', 'Standard', null],
                ['check',     'Promotional exposure', 'Standard', null],
                ['check',     'Member support', 'Standard', null],
            ],
            'callout'  => 'Monthly performance report with key metrics.',
        ],

        'explorer' => [
            'name'     => 'Explorer',
            'tagline'  => 'More exposure. More inquiries.',
            'badge'    => 'Most popular',
            'accent'   => 'green',
            'popular'  => true,
            'price'    => 1995,
            'heading'  => 'Everything in Starter, plus:',
            'blurb'    => 'Priority placement and the visibility tools that move listings fastest.',
            'features' => [
                ['check',     'Property profiles up to', '3 properties', null],
                ['check',     'Search visibility', 'Enhanced', null],
                ['check',     'Listing presentation', 'Enhanced', null],
                ['check',     'Featured listing', null, null],
                ['check',     'Priority placement', null, null],
                ['check',     'Inquiry notifications', 'Priority', null],
                ['check',     'Promotional exposure', 'Enhanced', null],
                ['google',    'Google Ads Campaigns', null, '2 campaigns with retargeting'],
                ['social3',   'Social Media Ads', null, 'Facebook, Instagram & TikTok'],
                ['mail',      'Email Marketing', null, '2 email blasts/month + automation'],
                ['seo',       'Advanced SEO & Local Optimization', null, null],
                ['audience',  'Audience Targeting & Retargeting', null, null],
                ['chart',     'Monthly Performance Report', null, 'with insights & recommendations'],
                ['support',   'Member support', 'Enhanced', null],
            ],
        ],

        'signature' => [
            'name'     => 'Signature',
            'tagline'  => 'Maximum visibility. Maximum results.',
            'badge'    => 'Best value',
            'accent'   => 'purple',
            'price'    => 3995,
            'heading'  => 'Everything in Explorer, plus:',
            'blurb'    => 'Top-of-results placement, campaigns across every channel, and a specialist of your own.',
            'features' => [
                ['check',     'Property profiles up to', '5 properties', null],
                ['check',     'Search visibility', 'Premier', null],
                ['check',     'Listing presentation', 'Premium', null],
                ['check',     'Priority placement', 'Highest priority', null],
                ['check',     'Inquiry notifications', 'Priority', null],
                ['check',     'Promotional exposure', 'Premium', null],
                ['check',     'Featured-area eligibility', null, null],
                ['google',    'Google Ads Campaigns', null, '3+ campaigns with retargeting & display'],
                ['social4',   'Full Social Media Campaigns', null, 'Facebook, Instagram, TikTok & YouTube'],
                ['mail',      'Email Marketing', null, 'Weekly email blasts + automation'],
                ['video',     'Video Marketing & Property Showcase', null, null],
                ['star',      'Reputation Management & Reviews', null, null],
                ['globe',     'Advanced Analytics Dashboard', null, 'Real-time performance tracking'],
                ['crown',     'Dedicated Account Manager', null, null],
                ['support',   'Member support', 'Priority', null],
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
