<?php

declare(strict_types=1);

namespace Src\Shared\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Src\Shared\Exceptions\HuggyApiException;
use Src\Shared\Services\Contracts\HuggyApiServiceInterface;

final class HuggyApiService implements HuggyApiServiceInterface
{
    private string $version = 'v3';

    private string $accessToken = '';

    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = mb_rtrim(
            (string) config('services.huggy.api_base_url', 'https://api.huggy.dev'),
            '/'
        );
    }

    public function v3(): static
    {
        $clone = clone $this;
        $clone->version = 'v3';

        return $clone;
    }

    public function v4(): static
    {
        $clone = clone $this;
        $clone->version = 'v4';

        return $clone;
    }

    public function withToken(string $token): static
    {
        $clone = clone $this;
        $clone->accessToken = $token;

        return $clone;
    }

    // ── v4 endpoints ────────────────────────────────────────────────────────

    /**
     * Returns the list of companies the authenticated user has access to.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws HuggyApiException
     */
    public function getUserCompanies(): array
    {
        $response = $this->http()->get($this->url('companies/1/users/me/companies'));

        if ($response->failed()) {
            throw new HuggyApiException(
                "Failed to fetch user companies: HTTP {$response->status()}"
            );
        }

        return $response->json() ?? [];
    }

    // ── internals ───────────────────────────────────────────────────────────

    private function http(): PendingRequest
    {
        return Http::withToken($this->accessToken)->acceptJson();
    }

    private function url(string $path): string
    {
        return "{$this->baseUrl}/{$this->version}/{$path}";
    }
}
