<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Interfaces\ProjectRepositoryInterface::class,
            \App\Repositories\Eloquent\ProjectRepository::class
        );
        
        $this->app->bind(
            \App\Repositories\Interfaces\TaskRepositoryInterface::class,
            \App\Repositories\Eloquent\TaskRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
