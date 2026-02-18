<?php

declare(strict_types=1);

namespace App\Data\Provider;

use App\Enums\ProviderType;

final class CreateProviderData
{
    public function __construct(
        public readonly ProviderType $providerType,
        public readonly string $credentials,
        public readonly ?string $settings = null,
    ) {}
}
