<?php

use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCurrentTermsAccepted;
use App\Http\Middleware\EnsureListingSpecialist;
use App\Http\Middleware\EnsurePermission;
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

        // Operator-toggled maintenance mode (setting general.maintenance_mode).
        // Appended so it runs after session/auth resolve — admins pass through.
        $middleware->web(append: [
            CheckMaintenanceMode::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
