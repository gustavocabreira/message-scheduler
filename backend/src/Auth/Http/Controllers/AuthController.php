<?php

declare(strict_types=1);

namespace Src\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Src\Auth\Actions\Contracts\SyncUserTenantsActionInterface;
use Src\Auth\Actions\HandleOAuthCallbackAction;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

final class AuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('huggy')->redirect();
    }

    public function callback(
        Request $request,
        HandleOAuthCallbackAction $handleCallback,
        SyncUserTenantsActionInterface $syncTenants,
    ): RedirectResponse {
        /** @var SocialiteUser $socialiteUser */
        $socialiteUser = Socialite::driver('huggy')->user();

        $user = $handleCallback->handle($socialiteUser);

        $tenants = $syncTenants->handle($user);

        $firstTenant = $tenants->first();

        if ($firstTenant !== null) {
            $request->session()->put('active_tenant_id', $firstTenant->id);
        }

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
