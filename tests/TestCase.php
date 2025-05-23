<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, MocksVite;

    /**
     * Indicates whether the default database setup should be run before each test.
     *
     * @var bool
     */
    protected $setUpHasRun = false;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Disable Vite for tests to avoid manifest not found errors
        $this->setUpMocksVite();

        // Avoid duplicate setUp calls
        if ($this->setUpHasRun) {
            return;
        }

        $this->setUpHasRun = true;
    }
}
