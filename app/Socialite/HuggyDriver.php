<?php

declare(strict_types=1);

namespace App\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class HuggyDriver extends AbstractProvider
{
    /** @var string[] */
    protected $scopes = ['install_app', 'read_agent_profile'];

    // No native type — overriding parent's untyped property
    protected $scopeSeparator = ' ';

    /**
     * OAuth2 server base URL (authorization + token endpoints).
     * Separate from the REST API base URL.
     */
    private function authBaseUrl(): string
    {
        return 'https://auth.huggy.dev';
    }

    /**
     * REST API base URL (user profile, contacts, messages).
     */
    private function apiUrl(): string
    {
        $url = config('services.huggy.api_url');

        return rtrim(is_string($url) ? $url : 'https://api.huggy.dev', '/');
    }

    protected function getAuthUrl(mixed $state): string
    {
        return $this->buildAuthUrlFromBase("{$this->authBaseUrl()}/oauth/authorize", (string) $state);
    }

    protected function getTokenUrl(): string
    {
        return "{$this->authBaseUrl()}/oauth/access_token";
    }

    /**
     * @return array<string, mixed>
     */
    protected function getUserByToken(mixed $token): array
    {
        $response = $this->getHttpClient()->get("{$this->apiUrl()}/v3/agents/profile", [
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
