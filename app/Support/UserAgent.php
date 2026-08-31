<?php

namespace App\Support;

/**
 * Coarse user-agent classification.
 *
 * Deliberately small and deliberately approximate. Advertising reporting needs
 * to answer "was this traffic mobile or desktop, and roughly what browser" -
 * it does not need version numbers, engine names, or the long tail of obscure
 * clients, and pulling in a full parsing library with a device database would
 * add a dependency and a periodic data update for an answer nobody reads that
 * closely.
 *
 * Anything unrecognized returns 'unknown' rather than guessing. An advertiser
 * reading "unknown" understands it; an advertiser reading a confidently wrong
 * "Safari on Windows" does not.
 *
 * Bots are classified but NOT discarded here. Whether crawler traffic counts
 * as an advertisement view is a reporting decision, and it belongs in the
 * query that counts, not in the recorder that observes.
 */
class UserAgent
{
    /** @return array{device_category:string, browser:?string, os:?string} */
    public static function parse(?string $ua): array
    {
        $ua = trim((string) $ua);

        if ($ua === '') {
            return ['device_category' => 'unknown', 'browser' => null, 'os' => null];
        }

        return [
            'device_category' => self::device($ua),
            'browser' => self::browser($ua),
            'os' => self::os($ua),
        ];
    }

    private static function device(string $ua): string
    {
        if (self::isBot($ua)) {
            return 'bot';
        }

        // Tablets first: an iPad's UA also matches the mobile patterns, so
        // testing mobile first would classify every tablet as a phone.
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) {
            return 'tablet';
        }

        if (preg_match('/Mobile|iPhone|iPod|Android|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
            return 'mobile';
        }

        if (preg_match('/Macintosh|Windows|Linux|CrOS|X11/i', $ua)) {
            return 'desktop';
        }

        return 'unknown';
    }

    public static function isBot(string $ua): bool
    {
        return (bool) preg_match(
            '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|lighthouse|pingdom|uptime|curl|wget|python-requests|axios|postman/i',
            $ua
        );
    }

    private static function browser(string $ua): ?string
    {
        // Order matters throughout: Edge and Opera both claim Chrome, Chrome
        // claims Safari. Most specific first, or everything reads as Safari.
        return match (true) {
            (bool) preg_match('/Edg[ei]?\//i', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/SamsungBrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/Firefox\/|FxiOS/i', $ua) => 'Firefox',
            (bool) preg_match('/Chrome\/|CriOS/i', $ua) => 'Chrome',
            (bool) preg_match('/Safari\//i', $ua) => 'Safari',
            default => null,
        };
    }

    private static function os(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/iPhone|iPad|iPod|iOS/i', $ua) => 'iOS',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/Windows NT/i', $ua) => 'Windows',
            (bool) preg_match('/Mac OS X|Macintosh/i', $ua) => 'macOS',
            (bool) preg_match('/CrOS/i', $ua) => 'ChromeOS',
            (bool) preg_match('/Linux|X11/i', $ua) => 'Linux',
            default => null,
        };
    }
}
