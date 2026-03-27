<?php

declare(strict_types=1);

namespace Src\Channel\Actions;

use Illuminate\Database\Eloquent\Collection;
use Src\Channel\Models\Channel;

final class ListChannelsAction
{
    /** @return Collection<int, Channel> */
    public function handle(?string $name, string $status = 'active'): Collection
    {
        return Channel::query()
            ->when(
                $status !== 'all',
                fn ($q) => $q->where('active', $status === 'active')
            )
            ->when(
                $name !== null,
                fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower((string) $name).'%'])
            )
            ->orderBy('name')
            ->get();
    }
}
