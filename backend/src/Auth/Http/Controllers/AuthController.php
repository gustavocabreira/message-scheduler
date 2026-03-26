<?php

declare(strict_types=1);

namespace Src\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $token = $user->createToken('huggy-oauth')->plainTextToken;

        $frontendUrl = mb_rtrim((string) config('app.frontend_url'), '/');

        return redirect("{$frontendUrl}/auth/callback?token={$token}");
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
