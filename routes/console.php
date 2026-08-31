<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Buyer offers expire at an exact clock time 24 hours after submission, so
// this runs every minute rather than hourly — an offer submitted at 19:30
// must read EXPIRED at 19:30 the next day, not at the top of the next hour.
// withoutOverlapping guards against a long sweep colliding with the next tick.
Schedule::command('offers:expire')->everyMinute()->withoutOverlapping();

// Retention for advertising traffic records. Section 8 of the privacy policy
// promises 24 months, including the IP addresses recorded with them — this is
// what makes that promise true rather than decorative, so it is not optional
// and must not be removed without changing the published policy first.
//
// Daily rather than hourly: the window is measured in months, so the exact
// minute a row crosses the line does not matter, and a single daily pass
// keeps the delete volume predictable.
Schedule::command('listora:prune-ad-events')->dailyAt('03:20')->withoutOverlapping();
