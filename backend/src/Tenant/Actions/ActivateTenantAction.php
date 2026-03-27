<?php

declare(strict_types=1);

namespace Src\Tenant\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Src\Tenant\Models\Tenant;

final class ActivateTenantAction
{
    public function handle(User $user, int $workspaceId): ?Tenant
    {
        $hasAccess = DB::connection('landlord')
            ->table('tenant_user')
            ->where('tenant_id', $workspaceId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $hasAccess) {
            return null;
        }

        $tenant = Tenant::query()->find($workspaceId);

        if (! $tenant instanceof Tenant) {
            return null;
        }

        $user->update(['last_workspace_id' => $tenant->id]);

        return $tenant;
    }
}
