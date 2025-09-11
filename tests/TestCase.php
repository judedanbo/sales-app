<?php

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // If the test uses RefreshDatabase, seed permissions
        if (in_array(RefreshDatabase::class, class_uses_recursive(static::class))) {
            $this->seed(RolesAndPermissionsSeeder::class);
        }
    }
}
