<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Src\Auth\Actions\Contracts\SyncUserTenantsActionInterface;
use Src\Auth\Actions\SyncUserTenantsAction;
use Src\Auth\Socialite\HuggySocialiteProvider;
use Src\Shared\Services\Contracts\HuggyApiServiceInterface;
use Src\Shared\Services\HuggyApiService;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HuggyApiServiceInterface::class, HuggyApiService::class);
        $this->app->bind(SyncUserTenantsActionInterface::class, SyncUserTenantsAction::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Socialite::extend('huggy', function () {
            $config = config('services.huggy');

            return new HuggySocialiteProvider(
                request: $this->app->make('request'),
                clientId: $config['client_id'],
                clientSecret: $config['client_secret'],
                redirectUrl: $config['redirect'],
            );
        });
    }
}
