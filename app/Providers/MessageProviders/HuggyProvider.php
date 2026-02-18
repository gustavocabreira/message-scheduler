<?php

declare(strict_types=1);

namespace App\Providers\MessageProviders;

use App\Contracts\MessageProvider;
use App\Models\ProviderConnection;
use Illuminate\Support\Facades\Http;

class HuggyProvider implements MessageProvider
{
    private string $apiUrl;

    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    /** @var array<string, mixed> */
    private array $credentials;

    public function __construct(ProviderConnection $connection)
    {
        $apiUrl = config('services.huggy.api_url');
        $clientId = config('services.huggy.client_id');
        $clientSecret = config('services.huggy.client_secret');
        $redirectUri = config('services.huggy.redirect_uri');

        $this->apiUrl = is_string($apiUrl) ? $apiUrl : 'https://api.huggy.app';
        $this->clientId = is_string($clientId) ? $clientId : '';
        $this->clientSecret = is_string($clientSecret) ? $clientSecret : '';
        $this->redirectUri = is_string($redirectUri) ? $redirectUri : '';
        $this->credentials = $connection->getDecryptedCredentials();
    }

    public function testConnection(): bool
    {
        $accessToken = $this->credentials['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            return false;
        }

        $response = Http::withToken($accessToken)
            ->get("{$this->apiUrl}/v3/me");

        return $response->successful();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getContacts(array $filters = []): array
    {
        $accessToken = $this->credentials['access_token'] ?? '';

        $response = Http::withToken(is_string($accessToken) ? $accessToken : '')
            ->get("{$this->apiUrl}/v3/contacts", $filters);

        if (! $response->successful()) {
            return [];
        }

        /** @var array<int, array<string, mixed>> */
        return $response->json('data', []);
    }

    public function sendMessage(string $contactId, string $message): bool
    {
        $accessToken = $this->credentials['access_token'] ?? '';

        $response = Http::withToken(is_string($accessToken) ? $accessToken : '')
            ->post("{$this->apiUrl}/v3/contacts/{$contactId}/messages", [
                'message' => $message,
            ]);

        return $response->successful();
    }

    public function getAuthorizationUrl(): string
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'contacts:read messages:write',
        ]);

        return "{$this->apiUrl}/oauth/authorize?{$params}";
    }

    /** @return array<string, mixed> */
    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::post("{$this->apiUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if (! $response->successful()) {
            return [];
        }

        /** @var array<string, mixed> */
        return $response->json();
    }

    /** @return array<string, mixed> */
    public function refreshTokens(string $refreshToken): array
    {
        $response = Http::post("{$this->apiUrl}/oauth/token", [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            return [];
        }

        /** @var array<string, mixed> */
        return $response->json();
    }
}
