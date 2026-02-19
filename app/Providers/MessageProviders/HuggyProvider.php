<?php

declare(strict_types=1);

namespace App\Providers\MessageProviders;

use App\Contracts\MessageProvider;
use App\Models\ProviderConnection;
use Illuminate\Support\Facades\Http;

class HuggyProvider implements MessageProvider
{
    private string $apiUrl;

    /** @var array<string, mixed> */
    private array $credentials;

    public function __construct(ProviderConnection $connection)
    {
        $apiUrl = config('services.huggy.api_url');

        $this->apiUrl = is_string($apiUrl) ? $apiUrl : 'https://api.huggy.app';
        $this->credentials = $connection->getDecryptedCredentials();
    }

    public function testConnection(): bool
    {
        $accessToken = $this->credentials['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            return false;
        }

        $response = Http::withToken($accessToken)
            ->get("{$this->apiUrl}/v3/agents/profile");

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
}
