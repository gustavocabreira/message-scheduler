<?php

declare(strict_types=1);

namespace App\Actions\Provider;

use App\Data\Provider\UpdateProviderData;
use App\Models\ProviderConnection;

class UpdateProviderConnectionAction
{
    public function execute(ProviderConnection $connection, UpdateProviderData $data): ProviderConnection
    {
        $connection->update([
            'credentials' => $data->credentials,
            'settings' => $data->settings !== null ? json_decode($data->settings, true) : $connection->settings,
        ]);

        return $connection->fresh() ?? $connection;
    }
}
