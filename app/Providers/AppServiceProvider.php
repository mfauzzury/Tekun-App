<?php

namespace App\Providers;

use App\Services\AiProviderService;
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
        // Saved Penyedia AI settings override ANTHROPIC_* env values.
        try {
            $this->app->make(AiProviderService::class)->applyToConfig();
        } catch (\Throwable) {
            // Settings table not available yet (fresh install, migrations pending).
        }
    }
}
