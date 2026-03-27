<?php

declare(strict_types=1);

namespace Src\Channel\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Src\Channel\Models\Channel;
use Src\Shared\Services\Contracts\HuggyApiServiceInterface;
use Src\Tenant\Models\Tenant;

final class ListChannelEntrypointsAction
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(private readonly HuggyApiServiceInterface $huggyApi) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(User $user, Tenant $tenant, Channel $channel): array
    {
        $cacheKey = "channels.entrypoints.{$tenant->id}.{$channel->slug}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, fn (): array => $this->huggyApi
            ->withToken((string) $user->huggy_access_token)
            ->v4()
            ->getChannelEntrypoints($tenant->id, $channel->slug)
        );
    }
}
