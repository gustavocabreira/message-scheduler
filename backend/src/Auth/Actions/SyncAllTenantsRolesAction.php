<?php

declare(strict_types=1);

namespace Src\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Src\Tenant\Models\Tenant;

final class SyncAllTenantsRolesAction
{
    public function __construct(
        private readonly SyncTenantRoleAction $syncTenantRole,
    ) {}

    /**
     * Syncs the user's role for every tenant they have access to.
     *
     * @param  Collection<int, Tenant>  $tenants
     */
    public function handle(User $user, Collection $tenants): void
    {
        foreach ($tenants as $tenant) {
            $this->syncTenantRole->handle($user, $tenant);
        }
    }
}
