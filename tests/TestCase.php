<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, MocksVite;
    
    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable Vite for tests to avoid manifest not found errors
        $this->setUpMocksVite();
    }
}
