<?php

namespace App\Services\Seo;

/**
 * Structured data and canonical tags for the home page.
 *
 * Companion to ExploreSeo, and deliberately a much smaller job. Explore has to
 * decide indexability per facet combination because it can generate effectively
 * unlimited URLs; the home page is one URL that is always indexable. What it
 * needs instead is identity — telling search engines who Listora is, how to
 * reach us, and that /browse is the search surface.
 *
 * The `WebSite` + `SearchAction` node is what makes a sitelinks search box
 * possible, and it points at /browse rather than the home page because that is
 * where a query actually resolves.
 *
 * Every value is read from config or settings rather than written here. The
 * contact address, name and social links appear on the page too, and a
 * structured-data block that disagrees with the visible page is worse than no
 * structured-data block at all.
 */
final class HomeSeo
{
    public function __construct(private readonly int $listingCount = 0)
    {
    }

    public function title(): string
    {
        return (string) setting('seo.meta_title_default', 'Listora — List. Connect. Explore.');
    }

    public function description(): string
    {
        return (string) setting(
            'seo.meta_description_default',
            'Browse vacation properties, resort club points, and vacation weeks advertised '
            .'directly by their owners. Listora never sits in the middle of the conversation.',
        );
    }

    public function canonical(): string
    {
        return route('home');
    }

    /**
     * The home page is always indexable, subject only to the site-wide kill
     * switch in Settings → SEO. There is no facet here that could produce a
     * thin or duplicate page.
     */
    public function robots(): string
    {
        return (bool) setting('seo.robots_index', true) ? 'index, follow' : 'noindex, follow';
    }

    /** Organization + WebSite (with SearchAction) as one graph. */
    public function jsonLd(): string
    {
        $brand = config('listora.brand');
        $siteName = (string) setting('general.site_name', $brand['name'] ?? 'Listora');
        $email = (string) setting('general.support_email', $brand['email'] ?? '');

        $organization = [
            '@type' => 'Organization',
            '@id' => url('/#organization'),
            'name' => $siteName,
            'url' => url('/'),
            'logo' => url('/img/logo.svg'),
            'description' => $this->description(),
            // Country only. Listora has no walk-in office, so a postal address
            // here would be an invitation we cannot honour.
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => $brand['location']['country'] ?? 'United States',
            ],
        ];

        if ($email !== '') {
            $organization['contactPoint'] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'email' => $email,
                'availableLanguage' => 'English',
            ];
        }

        if ($profiles = $this->socialProfiles()) {
            $organization['sameAs'] = $profiles;
        }

        $website = [
            '@type' => 'WebSite',
            '@id' => url('/#website'),
            'url' => url('/'),
            'name' => $siteName,
            'description' => $this->description(),
            'publisher' => ['@id' => url('/#organization')],
            'potentialAction' => [
                '@type' => 'SearchAction',
                // Points at Explore, not home: that is where a query resolves.
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('listings.index').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return (string) json_encode(
            ['@context' => 'https://schema.org', '@graph' => [$organization, $website]],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * Social URLs an operator has actually filled in.
     *
     * Empty settings are dropped rather than emitted as blank strings — a
     * `sameAs` pointing nowhere is a broken claim about identity.
     *
     * @return list<string>
     */
    private function socialProfiles(): array
    {
        $keys = [
            'general.social_instagram',
            'general.social_facebook',
            'general.social_tiktok',
            'general.social_x',
            'general.social_youtube',
        ];

        $urls = [];
        foreach ($keys as $key) {
            $value = trim((string) setting($key, ''));
            if ($value !== '') {
                $urls[] = $value;
            }
        }

        return $urls;
    }
}
