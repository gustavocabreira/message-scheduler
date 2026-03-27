<?php

declare(strict_types=1);

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Routing\Route;
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
        Socialite::extend('huggy', function ($app) {
            return Socialite::buildProvider(HuggySocialiteProvider::class, $app['config']['services.huggy']);
        });

        Scramble::routes(function (Route $route) {
            return str_starts_with($route->uri(), 'api/') || $route->uri() === 'api';
        });
    }
}
