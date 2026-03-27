<?php

declare(strict_types=1);

namespace Src\Tenant\TenantFinder;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

final class UserTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $workspaceId = $request->route('workspace');

        if (! $workspaceId) {
            return null;
        }

        $user = $request->user();

        if (! $user) {
            return null;
        }

        $hasAccess = DB::connection('landlord')
            ->table('tenant_user')
            ->where('tenant_id', $workspaceId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $hasAccess) {
            return null;
        }

        return Tenant::query()->where('id', $workspaceId)->first();
    }
}
