<?php

use App\Http\Middleware\CaptureLandingAttribution;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCurrentTermsAccepted;
use App\Http\Middleware\EnsureListingSpecialist;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\RecordPageView;
use App\Http\Middleware\SetListoraSurface;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // No CSRF exclusions. Listora takes no payments and integrates with no
        // processor, so there are no inbound webhooks to exempt — every POST
        // that reaches this app comes from a form we rendered.

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'admin.or.specialist' => EnsureListingSpecialist::class,
            'permission' => EnsurePermission::class,
            'terms.current' => EnsureCurrentTermsAccepted::class,
        ]);

        // Captures X-Listora-Surface on every API request so login_sessions and
        // tracking_events record the correct surface.
        $middleware->api(prepend: [
            SetListoraSurface::class,
        ]);

        // `lst_vid` is exempt from cookie encryption, deliberately.
        //
        // It is an opaque UUID with no personal data in it, and encryption
        // would buy nothing here: forging one only reassigns which attribution
        // bucket a visitor lands in. What encryption WOULD cost is real — the
        // cookie has a two-year lifetime to match the Google Ads attribution
        // window, and an encrypted value is tied to APP_KEY. Rotating the key,
        // or a second environment with a different one, would silently make
        // every existing cookie unreadable: returning visitors would be
        // re-cookied as new, and their original campaign credit lost, with
        // nothing in the logs to say so.
        //
        // Leaving it plaintext also lets client-side analytics stamp the same
        // id onto its payload as the database holds.
        $middleware->encryptCookies(except: [
            'lst_vid',
        ]);

        $middleware->web(append: [
            // Captures paid-click attribution on the page the click lands on.
            // Ordered BEFORE the maintenance gate on purpose: a campaign click
            // that arrives during maintenance was still paid for, and the 503
            // it gets should not also cost us the record of where it came from.
            CaptureLandingAttribution::class,

            // Operator-toggled maintenance mode (setting general.maintenance_mode).
            // Appended so it runs after session/auth resolve — admins pass through.
            CheckMaintenanceMode::class,

            // Writes a page_view for every rendered page, so a session
            // timeline shows the route someone took and not only the three
            // advertising steps along it. Last in the stack: it records after
            // the response, and only when there is a real page to record.
            RecordPageView::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
