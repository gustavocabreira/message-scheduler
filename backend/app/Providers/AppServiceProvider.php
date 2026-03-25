<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Src\Auth\Socialite\HuggySocialiteProvider;

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
        Socialite::extend('huggy', function () {
            $config = config('services.huggy');

            return new HuggySocialiteProvider(
                request: $this->app['request'],
                clientId: $config['client_id'],
                clientSecret: $config['client_secret'],
                redirectUrl: $config['redirect'],
            );
        });
    }
}
