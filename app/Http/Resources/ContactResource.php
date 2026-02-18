<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $resource */
        $resource = $this->resource;

        return [
            'id' => $resource['id'] ?? null,
            'name' => $resource['name'] ?? null,
            'phone' => $resource['phone'] ?? null,
            'email' => $resource['email'] ?? null,
        ];
    }
}
