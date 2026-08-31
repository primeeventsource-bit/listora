<?php

namespace App\Services\Advertising;

use Illuminate\Http\Request;

/**
 * Where a visit came from, in the categories an advertiser thinks in.
 *
 * Reports are read as "which of my sources produce inquiries", so the raw
 * utm_source string is stored alongside but this is what gets grouped. A
 * campaign tagged "Google", "google", "google-ads" and "googleads" across four
 * months should be one row in that report, not four.
 *
 * Order of precedence is explicit: an ad network's own click id beats a UTM
 * tag, which beats the referring host, which beats nothing at all. A visit
 * carrying a gclid came from Google Ads regardless of what its utm_source
 * claims, and mislabeling paid traffic as organic is the error that makes
 * advertising reporting useless.
 */
class AdTrafficSource
{
    public const GOOGLE_ADS = 'google_ads';
    public const META = 'meta';
    public const INSTAGRAM = 'instagram';
    public const MICROSOFT_ADS = 'microsoft_ads';
    public const TIKTOK = 'tiktok';
    public const EMAIL = 'email';
    public const ORGANIC = 'organic';
    public const REFERRAL = 'referral';
    public const DIRECT = 'direct';
    public const OTHER = 'other';

    public static function classify(Request $request, ?string $referrer = null): string
    {
        // 1. A network's own click id is definitive - it is minted by the
        //    network at click time and cannot be set by tagging a link wrong.
        if ($request->query('gclid') || $request->query('gbraid') || $request->query('wbraid')) {
            return self::GOOGLE_ADS;
        }
        if ($request->query('fbclid')) {
            return self::META;
        }
        if ($request->query('msclkid')) {
            return self::MICROSOFT_ADS;
        }
        if ($request->query('ttclid')) {
            return self::TIKTOK;
        }

        // 2. Campaign tagging.
        $source = strtolower((string) $request->query('utm_source'));
        $medium = strtolower((string) $request->query('utm_medium'));

        if ($source !== '' || $medium !== '') {
            if (str_contains($medium, 'email') || str_contains($source, 'email') || str_contains($source, 'newsletter')) {
                return self::EMAIL;
            }
            if (str_contains($source, 'instagram')) {
                return self::INSTAGRAM;
            }
            if (str_contains($source, 'facebook') || str_contains($source, 'meta')) {
                return self::META;
            }
            if (str_contains($source, 'google')) {
                return self::GOOGLE_ADS;
            }
            if (str_contains($source, 'bing') || str_contains($source, 'microsoft')) {
                return self::MICROSOFT_ADS;
            }
            if (str_contains($source, 'tiktok')) {
                return self::TIKTOK;
            }

            return self::OTHER;
        }

        // 3. Referring host.
        $host = $referrer ? strtolower((string) parse_url($referrer, PHP_URL_HOST)) : '';

        if ($host === '') {
            return self::DIRECT;
        }

        // Our own pages are not a traffic source.
        if (str_contains($host, (string) config('listora.brand.domain'))) {
            return self::DIRECT;
        }

        if (preg_match('/(^|\.)(google|bing|duckduckgo|yahoo|ecosia|brave)\./', $host)) {
            return self::ORGANIC;
        }
        if (str_contains($host, 'instagram.')) {
            return self::INSTAGRAM;
        }
        if (str_contains($host, 'facebook.') || str_contains($host, 'fb.')) {
            return self::META;
        }

        return self::REFERRAL;
    }

    /** Human labels for reports. */
    public static function label(string $category): string
    {
        return match ($category) {
            self::GOOGLE_ADS => 'Google Ads',
            self::META => 'Meta',
            self::INSTAGRAM => 'Instagram',
            self::MICROSOFT_ADS => 'Microsoft Ads',
            self::TIKTOK => 'TikTok',
            self::EMAIL => 'Email',
            self::ORGANIC => 'Organic search',
            self::REFERRAL => 'Referral',
            self::DIRECT => 'Direct',
            default => 'Other',
        };
    }
}
