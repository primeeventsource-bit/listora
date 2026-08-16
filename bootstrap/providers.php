<?php

use App\Providers\AppServiceProvider;
use App\Providers\TracingServiceProvider;

return [
    // Registered before AppServiceProvider so the Tracing singleton exists by
    // the time AppServiceProvider wires the traced Claude client around it.
    TracingServiceProvider::class,
    AppServiceProvider::class,
];
