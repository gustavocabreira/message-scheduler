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
}
