<?php

namespace Tests;

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\App;

trait MocksVite
{
    /**
     * Disable Vite for tests.
     * 
     * This prevents the "Vite manifest not found" error in feature tests
     * that render views.
     */
    protected function disableVite()
    {
        App::bind(Vite::class, function () {
            $vite = $this->createMock(Vite::class);
            $vite->method('__invoke')->willReturn('');
            $vite->method('reactRefresh')->willReturn('');
            $vite->method('content')->willReturn('');
            return $vite;
        });
    }
    
    /**
     * Set up the test environment.
     * 
     * @return void
     */
    protected function setUpMocksVite()
    {
        $this->disableVite();
    }
}
