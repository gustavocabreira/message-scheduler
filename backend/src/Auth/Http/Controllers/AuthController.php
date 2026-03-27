<?php

declare(strict_types=1);

namespace Src\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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
        HandleOAuthCallbackAction $handleCallback,
        SyncUserTenantsActionInterface $syncTenants,
    ): RedirectResponse {
        /** @var SocialiteUser $socialiteUser */
        $socialiteUser = Socialite::driver('huggy')->user();

        $user = $handleCallback->handle($socialiteUser);

        $syncTenants->handle($user);

        Auth::login($user);

        $frontendUrl = mb_rtrim((string) config('app.frontend_url'), '/');

        return redirect("{$frontendUrl}/auth/callback");
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
