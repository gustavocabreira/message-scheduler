<?php

declare(strict_types=1);

namespace App\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class HuggyDriver extends AbstractProvider
{
    /** @var string[] */
    protected $scopes = ['contacts:read', 'messages:write'];

    // No native type — overriding parent's untyped property
    protected $scopeSeparator = ' ';

    private function apiUrl(): string
    {
        $url = config('services.huggy.api_url');

        return is_string($url) ? $url : 'https://api.huggy.app';
    }

    protected function getAuthUrl(mixed $state): string
    {
        return $this->buildAuthUrlFromBase("{$this->apiUrl()}/oauth/authorize", (string) $state);
    }

    protected function getTokenUrl(): string
    {
        return "{$this->apiUrl()}/oauth/token";
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUserByToken(mixed $token): array
    {
        $response = $this->getHttpClient()->get("{$this->apiUrl()}/v3/me", [
            'headers' => ['Authorization' => 'Bearer '.(string) $token],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $user
     */
    protected function mapUserToObject(array $user): User
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'] ?? null,
            'name' => $user['name'] ?? null,
            'email' => $user['email'] ?? null,
        ]);
    }
}
