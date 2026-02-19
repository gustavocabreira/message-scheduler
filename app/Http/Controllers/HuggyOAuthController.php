<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use App\Models\ProviderConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class HuggyOAuthController extends Controller
{
    #[OA\Get(
        path: '/auth/huggy/redirect',
        summary: 'Get Huggy OAuth2 authorization URL',
        security: [['bearerAuth' => []]],
        tags: ['Auth', 'Providers'],
        responses: [
            new OA\Response(response: 200, description: 'Authorization URL to redirect user to'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function redirect(): JsonResponse
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver('huggy');

        /** @var SymfonyRedirect $redirectResponse */
        $redirectResponse = $driver->stateless()->redirect();

        return response()->json([
            'authorization_url' => $redirectResponse->getTargetUrl(),
        ]);
    }

    #[OA\Get(
        path: '/auth/huggy/callback',
        summary: 'Handle Huggy OAuth2 callback and exchange code for tokens',
        security: [['bearerAuth' => []]],
        tags: ['Auth', 'Providers'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tokens stored, provider connected'),
            new OA\Response(response: 400, description: 'Missing code or authentication failed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function callback(Request $request): JsonResponse
    {
        $code = $request->query('code');

        if (! is_string($code) || $code === '') {
            return response()->json(['message' => 'Authorization code is required.'], 400);
        }

        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver('huggy');

            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = $driver->stateless()->user();
        } catch (\Exception $e) {
            $connection = $this->getOrCreateConnection($request);
            $connection->update(['status' => ProviderStatus::ERROR->value]);

            return response()->json(['message' => 'Failed to exchange code for tokens.'], 400);
        }

        $connection = $this->getOrCreateConnection($request);
        $connection->update([
            'credentials' => json_encode([
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken,
                'expires_in' => $socialiteUser->expiresIn,
            ], JSON_THROW_ON_ERROR),
            'status' => ProviderStatus::ACTIVE->value,
            'connected_at' => now(),
        ]);

        return response()->json([
            'message' => 'Huggy account connected successfully.',
        ]);
    }

    private function getOrCreateConnection(Request $request): ProviderConnection
    {
        $user = $request->user();

        /** @var ProviderConnection */
        return ProviderConnection::firstOrCreate(
            ['user_id' => $user?->id, 'provider_type' => ProviderType::HUGGY->value],
            ['status' => ProviderStatus::INACTIVE->value, 'credentials' => json_encode([])],
        );
    }
}
