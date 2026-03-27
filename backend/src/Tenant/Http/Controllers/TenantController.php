<?php

declare(strict_types=1);

namespace Src\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Tenant\Http\Requests\ListTenantsRequest;
use Src\Tenant\Http\Resources\TenantResource;
use Src\Tenant\Models\Tenant;

final class TenantController extends Controller
{
    public function index(ListTenantsRequest $request): AnonymousResourceCollection
    {
        $tenants = Tenant::query()
            ->join('tenant_user', 'tenants.id', '=', 'tenant_user.tenant_id')
            ->where('tenant_user.user_id', $request->user()->id)
            ->when(
                $request->string('name')->isNotEmpty(),
                fn ($q) => $q->whereRaw('LOWER(tenants.name) LIKE ?', ['%'.strtolower((string) $request->string('name')).'%'])
            )
            ->orderBy('tenants.name')
            ->select('tenants.*')
            ->paginate(10);

        return TenantResource::collection($tenants);
    }
}
