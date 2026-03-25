<?php

declare(strict_types=1);

namespace Src\Shared\Services\Contracts;

use Src\Shared\Exceptions\HuggyApiException;

interface HuggyApiServiceInterface
{
    public function v3(): static;

    public function v4(): static;

    public function withToken(string $token): static;

    /**
     * Returns the list of companies the authenticated user has access to.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws HuggyApiException
     */
    public function getUserCompanies(): array;
}
