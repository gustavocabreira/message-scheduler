<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use App\Models\ProviderConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin ProviderConnection */
class ProviderConnectionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var ProviderType $providerType */
        $providerType = $this->provider_type;
        /** @var ProviderStatus $status */
        $status = $this->status;
        /** @var Carbon|null $connectedAt */
        $connectedAt = $this->connected_at;
        /** @var Carbon|null $lastSyncedAt */
        $lastSyncedAt = $this->last_synced_at;
        /** @var Carbon|null $createdAt */
        $createdAt = $this->created_at;

        return [
            'id' => $this->id,
            'provider_type' => $providerType->value,
            'provider_label' => $providerType->label(),
            'status' => $status->value,
            'settings' => $this->settings,
            'connected_at' => $connectedAt?->toIso8601String(),
            'last_synced_at' => $lastSyncedAt?->toIso8601String(),
            'created_at' => $createdAt?->toIso8601String(),
        ];
    }
}
