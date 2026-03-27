<?php

declare(strict_types=1);

namespace Src\Tenant\Actions;

use App\Models\User;
use Src\Tenant\Models\Tenant;

final class GetActiveTenantAction
{
    public function handle(User $user, ?int $sessionTenantId): ?Tenant
    {
        $tenantId = $sessionTenantId ?? $user->refresh()->last_workspace_id;

        if (! $tenantId) {
            return null;
        }

        $tenant = Tenant::query()->find($tenantId);

        return $tenant instanceof Tenant ? $tenant : null;
    }
}
