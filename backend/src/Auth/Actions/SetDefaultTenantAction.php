<?php

declare(strict_types=1);

namespace Src\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Src\Tenant\Models\Tenant;

final class SetDefaultTenantAction
{
    /** @param Collection<int, Tenant> $tenants */
    public function handle(User $user, Collection $tenants): void
    {
        if ($user->last_workspace_id !== null || $tenants->isEmpty()) {
            return;
        }

        /** @var Tenant $first */
        $first = $tenants->first();

        $user->update(['last_workspace_id' => $first->id]);
    }
}
