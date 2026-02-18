<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use App\Models\ProviderConnection;
use App\Providers\MessageProviders\HuggyProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

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
    public function redirect(Request $request): JsonResponse
    {
        $connection = $this->getOrCreateConnection($request);
        $provider = new HuggyProvider($connection);

        return response()->json([
            'authorization_url' => $provider->getAuthorizationUrl(),
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
            new OA\Response(response: 400, description: 'Missing code or token exchange failed'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function callback(Request $request): JsonResponse
    {
        $code = $request->query('code');

        if (! is_string($code) || $code === '') {
            return response()->json(['message' => 'Authorization code is required.'], 400);
        }

        $connection = $this->getOrCreateConnection($request);
        $provider = new HuggyProvider($connection);
        $tokens = $provider->exchangeCodeForTokens($code);

        if (empty($tokens) || ! isset($tokens['access_token'])) {
            $connection->update(['status' => ProviderStatus::ERROR->value]);

            return response()->json(['message' => 'Failed to exchange code for tokens.'], 400);
        }

        $connection->update([
            'credentials' => json_encode($tokens),
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
