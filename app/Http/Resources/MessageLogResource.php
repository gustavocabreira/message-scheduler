<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin MessageLog */
class MessageLogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Carbon|null $createdAt */
        $createdAt = $this->created_at;

        return [
            'id' => $this->id,
            'attempt' => $this->attempt,
            'status' => $this->status,
            'response' => $this->response,
            'error_message' => $this->error_message,
            'created_at' => $createdAt?->toIso8601String(),
        ];
    }
}
