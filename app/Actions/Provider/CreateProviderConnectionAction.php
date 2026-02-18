<?php

declare(strict_types=1);

namespace App\Actions\Provider;

use App\Data\Provider\CreateProviderData;
use App\Enums\ProviderStatus;
use App\Models\ProviderConnection;
use App\Models\User;

class CreateProviderConnectionAction
{
    public function execute(User $user, CreateProviderData $data): ProviderConnection
    {
        /** @var ProviderConnection */
        return $user->providerConnections()->create([
            'provider_type' => $data->providerType->value,
            'status' => ProviderStatus::INACTIVE->value,
            'credentials' => $data->credentials,
            'settings' => $data->settings !== null ? json_decode($data->settings, true) : null,
        ]);
    }
}
