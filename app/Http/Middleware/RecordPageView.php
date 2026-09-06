<?php

namespace App\Http\Middleware;

use App\Enums\AdEventType;
use App\Services\Advertising\AdEventRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a page view for every visited page.
 *
 * The activity log is meant to answer "what did this person actually do, in
 * order" - homepage, then vacation properties, then a listing, then send
 * inquiry. Recording only the advertising funnel gave the third step and
 * nothing around it, so a session timeline had holes in exactly the places
 * that explain the rest of it.
 *
 * Records AFTER the response, and only for a successful HTML GET. Four
 * consequences worth stating:
 *
 *  - A request that 404s or redirects writes nothing. A log full of pages
 *    nobody actually saw is worse than a shorter one.
 *  - A POST writes nothing here. Form submissions record their own, specific
 *    events (inquiry submitted, offer submitted), and a page_view beside them
 *    would double-count the same action under a vaguer name.
 *  - Assets, health checks and the tracking endpoint are excluded. They are
 *    requests the browser made, not pages a person looked at.
 *  - Listing and advertisement pages are excluded, because AdController
 *    already records those with the listing attached. Recording both would
 *    put every listing view in the table twice, and inflate the view count an
 *    advertiser is paying for.
 *
 * Failures are swallowed by the recorder. A page must not 500 because the
 * note about it could not be written.
 */
class RecordPageView
{
    /**
     * Paths that are never a page view.
     *
     * Prefix matches. `ad` and `listings` are here because AdController
     * records those itself, with the listing attached - which is the version
     * worth keeping.
     */
    private const IGNORED = [
        'up',
        'api',
        'track',
        'build',
        'css',
        'js',
        'img',
        'fonts',
        'storage',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',
        'ad',
        'listing',
        'listings',
        'livewire',
        '_debugbar',
        '_ignition',
    ];

    public function __construct(private readonly AdEventRecorder $recorder)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            $this->recorder->record($request, AdEventType::PageView);
        }

        return $response;
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        // 2xx only. A redirect is a page the visitor passed through without
        // reading, and an error is a page that was not there.
        if ($response->getStatusCode() >= 300) {
            return false;
        }

        // Anything that is not a rendered page - JSON, a file download, an
        // image - is a request the browser made rather than something a
        // person looked at.
        $contentType = (string) $response->headers->get('Content-Type');

        if ($contentType !== '' && ! str_contains($contentType, 'text/html')) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson()) {
            return false;
        }

        return ! $this->isIgnored($request->path());
    }

    private function isIgnored(string $path): bool
    {
        $first = explode('/', trim($path, '/'))[0] ?? '';

        return in_array($first, self::IGNORED, true);
    }
}
