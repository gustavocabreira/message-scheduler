<?php

namespace Src\Tenant\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class UserTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $tenantId = $request->session()->get('active_tenant_id');

        if (! $tenantId) {
            return null;
        }

        return Tenant::find($tenantId);
    }
}
