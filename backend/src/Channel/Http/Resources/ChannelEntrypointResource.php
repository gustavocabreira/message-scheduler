<?php

declare(strict_types=1);

namespace Src\Channel\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ChannelEntrypointResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'name' => $this->resource['name'],
            'type' => $this->resource['type'],
            'uuid' => $this->resource['uuid'],
            'provider' => $this->resource['provider'],
            'entrypoint' => $this->resource['entrypoint'],
        ];
    }
}
