<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MessageProvider;
use App\Enums\ProviderType;
use App\Models\ProviderConnection;
use App\Providers\MessageProviders\HuggyProvider;

class ProviderFactory
{
    public function make(ProviderType $type, ProviderConnection $connection): MessageProvider
    {
        return match ($type) {
            ProviderType::HUGGY => new HuggyProvider($connection),
        };
    }
}
