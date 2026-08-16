<?php

use App\Http\Controllers\Api\Admin\SettingsApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\LoginHistoryController;
use App\Http\Controllers\Api\PublicSettingsController;
use App\Http\Controllers\Api\SupportChatController;
use App\Http\Controllers\Api\TrackingEventController;
use Illuminate\Support\Facades\Route;

// All routes here are mounted under /api/v1 by bootstrap/app.php.
// Anything inside ->middleware('auth:sanctum') requires a Sanctum bearer token.

// Throttled deliberately and tightly. The web login is capped at 5 attempts
// per email+IP (Auth\LoginRequest::ensureIsNotRateLimited), but this endpoint
// hits the SAME credential store and would otherwise have no limit at all —
// an unmetered password-guessing oracle that mints a non-expiring Sanctum
// token on success. The API middleware group applies no throttle of its own:
// Laravel only joins one when withRouting(apiLimiter:) is configured, and it
// is not.
Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Public listing search + show — no auth required (browse without an account).
Route::get('listings', [ListingController::class, 'index']);
Route::get('listings/{listing}', [ListingController::class, 'show']);

// Public tracking ingest (anonymous or auth-optional) — rate limited.
Route::post('tracking/events', [TrackingEventController::class, 'store'])
    ->middleware('throttle:60,1');

// AI support chat. Anonymous chat allowed for marketing-page visitors;
// auth-optional via Sanctum guard. Per-IP rate-limited.
Route::post('support/chat', [SupportChatController::class, 'chat'])
    ->middleware('throttle:30,1');

// Public frontend bootstrap: is_public settings + resolved feature flags.
// Sensitive values never appear here (structurally excluded, not filtered).
Route::get('settings/public', PublicSettingsController::class)
    ->middleware('throttle:120,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // There is deliberately no endpoint that creates a booking, takes a
    // payment, or reads a charge. Listora advertises listings and settles no
    // money; an API that appeared to do otherwise would contradict the
    // product just as much as a checkout page would.

    // The signed-in user's own auth records + activity map.
    Route::get('me/login-history', [LoginHistoryController::class, 'mine']);
    Route::get('me/activity-map', [LoginHistoryController::class, 'activityMap']);
});

// Admin settings API — Sanctum token + the same granular RBAC permissions the
// web console uses, so a token inherits exactly its owner's role, no more.
// Sensitive VALUES stay redacted in responses regardless of permission
// (SettingsRepository::redact).
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('settings', [SettingsApiController::class, 'index'])->middleware('permission:settings.view');
    Route::get('settings/{group}', [SettingsApiController::class, 'showGroup'])->middleware('permission:settings.view');
    Route::put('settings/{group}', [SettingsApiController::class, 'updateGroup'])->middleware('permission:settings.edit');
    Route::get('feature-flags', [SettingsApiController::class, 'flags'])->middleware('permission:settings.view');
    Route::put('feature-flags/{key}', [SettingsApiController::class, 'updateFlag'])->middleware('permission:settings.edit');
});
