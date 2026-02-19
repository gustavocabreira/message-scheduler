<?php

declare(strict_types=1);

namespace App\Providers;

use App\Socialite\HuggyDriver;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Socialite::extend('huggy', function (): HuggyDriver {
            /** @var array{client_id: string, client_secret: string, redirect: string, api_url?: string} $config */
            $config = config('services.huggy');

            /** @var HuggyDriver */
            return Socialite::buildProvider(HuggyDriver::class, $config);
        });
    }
}
