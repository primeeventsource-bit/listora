@php
    // Both IDs are operator-set in Settings → Integrations. Unset means unset:
    // no tag, no network call, no cookie. A marketing tag that ships hardcoded
    // is one that fires on every developer's laptop and in every test run.
    $analyticsId = trim((string) setting('integrations.analytics_id', ''));
    $googleAdsId = trim((string) setting('integrations.google_ads_id', ''));
    $tagIds      = array_values(array_filter([$analyticsId, $googleAdsId]));
@endphp

@if ($tagIds)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $tagIds[0] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        @foreach ($tagIds as $tagId)
            gtag('config', @json($tagId), {
                // Lets Google Ads read the gclid this app already stores
                // server-side, so click and conversion reconcile on one id
                // rather than two systems each holding half the journey.
                'allow_enhanced_conversions': true
            });
        @endforeach

        @isset($visitorId)
            @if ($visitorId)
                // The same opaque id written to ppc_visitors. Not a user id and
                // not derived from one — it is what joins an Ads click to the
                // inquiry it eventually produced.
                gtag('set', {'user_id': @json($visitorId)});
            @endif
        @endisset

        @stack('analytics')
    </script>
@endif
