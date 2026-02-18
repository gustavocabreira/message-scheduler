<?php

declare(strict_types=1);

namespace App\Contracts;

interface MessageProvider
{
    public function testConnection(): bool;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getContacts(array $filters = []): array;

    public function sendMessage(string $contactId, string $message): bool;

    public function getAuthorizationUrl(): string;

    /** @return array<string, mixed> */
    public function exchangeCodeForTokens(string $code): array;

    /** @return array<string, mixed> */
    public function refreshTokens(string $refreshToken): array;
}
