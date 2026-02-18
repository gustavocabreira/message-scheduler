<?php

declare(strict_types=1);

namespace App\Data\Provider;

final class UpdateProviderData
{
    public function __construct(
        public readonly string $credentials,
        public readonly ?string $settings = null,
    ) {}
}
