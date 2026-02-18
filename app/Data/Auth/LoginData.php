<?php

declare(strict_types=1);

namespace App\Data\Auth;

final class LoginData
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $deviceName = 'api',
    ) {}

    /** @param array{email: string, password: string, device_name?: string} $data */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            deviceName: $data['device_name'] ?? 'api',
        );
    }
}
