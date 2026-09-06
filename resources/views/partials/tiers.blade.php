{{--
    The three advertising plans.

    Shared by /pricing and the home page, so the price an owner sees on the
    way in is the price on the plan page by construction rather than by
    someone remembering to change both.

    Rows carry an icon key because the plans sell work on named platforms -
    Google Ads, Facebook, Instagram, TikTok, YouTube - and a column of
    identical check marks flattens "priority placement" and "we will run your
    Google Ads" into the same claim. The brand marks are drawn here rather
    than loaded, because this page must render with no third-party request:
    an ad blocker or a blocked CDN would otherwise leave a paid pricing page
    full of broken icons.
--}}
@php
    $icons = [
        'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12.5l5.2 5.2L20 6.6"/></svg>',

        // Google Ads: the angled yellow and blue bars with the green dot.
        'google' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9.6 2.9a2.6 2.6 0 0 1 4.5 2.6L8.6 15a2.6 2.6 0 1 1-4.5-2.6z" fill="#FBBC04"/><path d="M14.1 2.9a2.6 2.6 0 0 0-4.5 2.6L15.1 15a2.6 2.6 0 1 0 4.5-2.6z" fill="#4285F4"/><circle cx="6.4" cy="18.4" r="2.8" fill="#34A853"/></svg>',

        'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="11" fill="#1877F2"/><path d="M15.1 12.6l.4-2.7h-2.6V8.1c0-.8.4-1.5 1.6-1.5h1.2V4.3s-1.1-.2-2.1-.2c-2.1 0-3.5 1.3-3.5 3.6v2.2H7.7v2.7h2.4v6.6a9.6 9.6 0 0 0 3 0v-6.6z" fill="#fff"/></svg>',

        'instagram' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2.4" y="2.4" width="19.2" height="19.2" rx="5.4" fill="#E1306C"/><rect x="6.6" y="6.6" width="10.8" height="10.8" rx="4" fill="none" stroke="#fff" stroke-width="1.8"/><circle cx="17.4" cy="6.6" r="1.25" fill="#fff"/></svg>',

        'tiktok' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2.4" y="2.4" width="19.2" height="19.2" rx="5" fill="#111"/><path d="M13.4 5.6v7.7a2 2 0 1 1-1.7-2v-2a4 4 0 1 0 3.7 4V9.5a5 5 0 0 0 2.6.8V8.4a3 3 0 0 1-2.9-2.8z" fill="#fff"/></svg>',

        'youtube' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="1.6" y="4.6" width="20.8" height="14.8" rx="4.2" fill="#FF0000"/><path d="M10.2 8.7l5.6 3.3-5.6 3.3z" fill="#fff"/></svg>',

        'mail' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2.6" y="5" width="18.8" height="14" rx="2.4"/><path d="M3.4 6.6L12 13l8.6-6.4"/></svg>',

        'seo' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.6" cy="10.6" r="6.4"/><path d="M15.4 15.4L21 21"/><path d="M7.6 10.6h6M10.6 7.6v6"/></svg>',

        'audience' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8.4" r="3"/><path d="M3.4 19.2a5.6 5.6 0 0 1 11.2 0"/><circle cx="17.4" cy="9.4" r="2.4"/><path d="M15.6 19.2a4.6 4.6 0 0 1 5-4.4"/></svg>',

        'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"><path d="M5 20v-6M12 20V5M19 20v-9"/></svg>',

        'video' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="2.6" y="5.2" width="18.8" height="13.6" rx="3.2"/><path d="M10 9.6l5 2.6-5 2.6z"/></svg>',

        'star' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.2l2.6 5.5 6 .8-4.4 4.2 1.1 6-5.3-2.9-5.3 2.9 1.1-6L3.4 9.5l6-.8z"/></svg>',

        'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3.2 12h17.6"/><path d="M12 3a14 14 0 0 1 0 18 14 14 0 0 1 0-18z"/></svg>',

        'crown' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 8.4l3.8 3L12 4.6l5.2 6.8 3.8-3-2 9.6H5z"/><rect x="5" y="18.4" width="14" height="1.9" rx=".9"/></svg>',

        'support' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4.4 15.4v-3.2a7.6 7.6 0 0 1 15.2 0v3.2"/><rect x="2.6" y="13.6" width="4" height="6" rx="2"/><rect x="17.4" y="13.6" width="4" height="6" rx="2"/><path d="M19.6 19.6a3.6 3.6 0 0 1-3.6 2.6h-2"/></svg>',
    ];

    $icons['social3'] = $icons['facebook'].$icons['instagram'].$icons['tiktok'];
    $icons['social4'] = $icons['facebook'].$icons['instagram'].$icons['tiktok'].$icons['youtube'];
@endphp

<div class="ptiers">
    @foreach ($plans as $key => $plan)
        <section class="ptier ptier--{{ $plan['accent'] }} {{ ($plan['popular'] ?? false) ? 'is-popular' : '' }}"
                 aria-labelledby="plan-{{ $key }}">

            <header class="ptier__head">
                <div>
                    <h3 class="ptier__name" id="plan-{{ $key }}">{{ $plan['name'] }}</h3>
                    <p class="ptier__tagline">{{ $plan['tagline'] }}</p>
                </div>
                <span class="ptier__badge">{{ $plan['badge'] }}</span>
            </header>

            <p class="ptier__heading">{{ $plan['heading'] }}</p>

            <ul class="ptier__list">
                @foreach ($plan['features'] as [$icon, $text, $strong, $note])
                    <li class="ptier__row">
                        <span class="ptier__icon ptier__icon--{{ $icon }}" aria-hidden="true">{!! $icons[$icon] !!}</span>
                        <span class="ptier__text">{{ $text }}@if ($strong) <b>{{ $strong }}</b>@endif @if ($note)<i>({{ $note }})</i>@endif</span>
                    </li>
                @endforeach
            </ul>

            @if (! empty($plan['callout']))
                <p class="ptier__callout">
                    <span class="ptier__icon ptier__icon--chart" aria-hidden="true">{!! $icons['chart'] !!}</span>
                    {{ $plan['callout'] }}
                </p>
            @endif

            <div class="ptier__price">
                <span class="ptier__amt">${{ number_format($plan['price']) }}</span><span
                      class="ptier__per">/ {{ \App\Enums\PlanTier::from($key)->termDays() }} day listing</span>
                <span class="ptier__billed">Billed upfront</span>
            </div>

            <a href="{{ route('list.create', ['plan' => $key]) }}" class="ptier__cta">
                Choose {{ $plan['name'] }}
            </a>
        </section>
    @endforeach
</div>
