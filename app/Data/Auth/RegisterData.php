<?php

declare(strict_types=1);

namespace App\Data\Auth;

final class RegisterData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $timezone = 'UTC',
    ) {}

    /** @param array{name: string, email: string, password: string, timezone?: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            timezone: $data['timezone'] ?? 'UTC',
        );
    }
}
