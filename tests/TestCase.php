<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    /**
     * Start every test with an empty cache.
     *
     * FeatureFlagService caches the whole flag table, and that cache is not
     * rolled back by RefreshDatabase - only the rows are. So a test that
     * enables a flag leaves the cached value behind for whatever runs next,
     * and the victim fails only when the suite runs in a particular order.
     *
     * That is a debugging trap out of proportion to the cost of clearing it:
     * the failing test passes on its own, which sends you looking at the test
     * rather than at the one before it.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }
}
