<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Force HTTPS for non-local domain hosts (e.g. wisuda.poltekindonusa.ac.id)
        if (!in_array(request()->getHost(), ['localhost', '127.0.0.1'])) {
            URL::forceScheme('https');
        }
    }
}
