<?php

declare(strict_types=1);

namespace App\Actions\Provider;

use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use App\Models\ProviderConnection;
use App\Services\ProviderFactory;

class TestProviderConnectionAction
{
    public function __construct(private readonly ProviderFactory $factory) {}

    public function execute(ProviderConnection $connection): bool
    {
        /** @var ProviderType $providerType */
        $providerType = $connection->provider_type;
        $provider = $this->factory->make($providerType, $connection);
        $isConnected = $provider->testConnection();

        $connection->update([
            'status' => $isConnected ? ProviderStatus::ACTIVE->value : ProviderStatus::ERROR->value,
            'last_synced_at' => $isConnected ? now() : $connection->last_synced_at,
        ]);

        return $isConnected;
    }
}
