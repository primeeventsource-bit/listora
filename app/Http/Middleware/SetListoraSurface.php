<?php

namespace App\Http\Middleware;

use App\Enums\Surface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captures the X-Listora-Surface header into request attributes so downstream
 * code (login_sessions, tracking_events) records the correct surface (FR-10.7).
 *
 * Header values: 'web' | 'app_ios' | 'app_android' | 'admin'.
 * Anything else (or missing) defaults to 'web'.
 */
class SetListoraSurface
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->header('X-Listora-Surface');
        $surface = Surface::tryFrom((string) $raw) ?? Surface::Web;

        $request->attributes->set('listora_surface', $surface);

        return $next($request);
    }
}
