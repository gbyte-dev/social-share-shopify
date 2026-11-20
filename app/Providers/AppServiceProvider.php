<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        $appUrl = config('shopify.app_url') ?: config('app.url');

        if (filled($appUrl)) {
            $normalizedUrl = rtrim((string) $appUrl, '/');
            URL::forceRootUrl($normalizedUrl);

            if (parse_url($normalizedUrl, PHP_URL_SCHEME) === 'https') {
                URL::forceScheme('https');
            }
        }
    }
}
