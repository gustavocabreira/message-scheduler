<?php

declare(strict_types=1);

namespace Src\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Src\Auth\Actions\Contracts\SyncUserTenantsActionInterface;
use Src\Auth\Actions\HandleOAuthCallbackAction;
use Src\Auth\Actions\LogoutAction;
use Src\Auth\Actions\SetDefaultTenantAction;
use Src\Auth\Actions\SyncAllTenantsRolesAction;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

#[Group(name: 'Auth')]
final class AuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('huggy')->redirect();
    }

    public function callback(
        HandleOAuthCallbackAction $handleCallback,
        SyncUserTenantsActionInterface $syncTenants,
        SyncAllTenantsRolesAction $syncAllRoles,
        SetDefaultTenantAction $setDefaultTenant,
    ): RedirectResponse {
        /** @var SocialiteUser $socialiteUser */
        $socialiteUser = Socialite::driver('huggy')->user();

        $user = $handleCallback->handle($socialiteUser);
        $tenants = $syncTenants->handle($user);
        $syncAllRoles->handle($user, $tenants);
        $setDefaultTenant->handle($user, $tenants);

        Auth::login($user);

        $frontendUrl = mb_rtrim((string) config('app.frontend_url'), '/');

        return redirect("{$frontendUrl}/auth/callback");
    }

    #[Endpoint(
        operationId: 'auth.logout',
        title: 'Logout',
        description: 'Encerra a sessão autenticada do usuário, invalidando o cookie de sessão.',
    )]
    #[Response(status: 200, description: 'Sessão encerrada com sucesso.')]
    public function logout(LogoutAction $action, Request $request): JsonResponse
    {
        $action->handle($request);

        return response()->json(['message' => 'Logged out successfully']);
    }
}
