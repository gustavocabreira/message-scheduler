<?php

declare(strict_types=1);

namespace Src\Tenant\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

final class UserTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        if (! $request->hasSession()) {
            return null;
        }

        $tenantId = $request->session()->get('active_tenant_id');

        if (! $tenantId) {
            return null;
        }

        return Tenant::where('id', $tenantId)->first();
    }
}
