<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Disable database seeders by default in tests.
     *
     * This prevents PlaceSeeder from running and adding 50+ restaurants
     * which interferes with geospatial and other precision tests.
     *
     * Individual tests can override this by setting $seed = true.
     *
     * @var bool
     */
    protected $seed = false;
}
