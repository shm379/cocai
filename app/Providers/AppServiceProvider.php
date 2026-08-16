<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\URL::forceScheme('https');
        $rootUrl = config('app.url');
        if (! $rootUrl || $rootUrl === 'http://localhost') {
            $rootUrl = 'https://cocai.nabuxai.com';
        }
        \Illuminate\Support\Facades\URL::forceRootUrl($rootUrl);
        Vite::prefetch(concurrency: 3);
    }
}
