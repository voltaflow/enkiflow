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
        
        $this->app->bind(
            \App\Repositories\Interfaces\TimeEntryRepositoryInterface::class,
            \App\Repositories\Eloquent\TimeEntryRepository::class
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
