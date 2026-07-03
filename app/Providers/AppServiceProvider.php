<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('database.default') === 'sqlite' && ! $this->app->environment('testing')) {
            throw new \RuntimeException(
                'SQLite is only permitted in the testing environment. Set DB_CONNECTION=mysql in your .env (see .env.example).'
            );
        }
    }
}
