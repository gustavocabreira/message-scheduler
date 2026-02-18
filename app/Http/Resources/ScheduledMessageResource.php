<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ScheduledMessageStatus;
use App\Models\ScheduledMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin ScheduledMessage */
class ScheduledMessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var ScheduledMessageStatus $status */
        $status = $this->status;
        /** @var Carbon $scheduledAt */
        $scheduledAt = $this->scheduled_at;
        /** @var Carbon|null $createdAt */
        $createdAt = $this->created_at;
        /** @var Carbon|null $updatedAt */
        $updatedAt = $this->updated_at;

        return [
            'id' => $this->id,
            'provider_connection_id' => $this->provider_connection_id,
            'contact_id' => $this->contact_id,
            'contact_name' => $this->contact_name,
            'message' => $this->message,
            'scheduled_at' => $scheduledAt->toIso8601String(),
            'status' => $status->value,
            'attempts' => $this->attempts,
            'created_at' => $createdAt?->toIso8601String(),
            'updated_at' => $updatedAt?->toIso8601String(),
        ];
    }
}
