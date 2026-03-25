<?php

declare(strict_types=1);

namespace Src\Auth\Actions\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;
use Src\Tenant\Models\Tenant;

interface SyncUserTenantsActionInterface
{
    /**
     * @return Collection<int, Tenant>
     */
    public function handle(User $user): Collection;
}
