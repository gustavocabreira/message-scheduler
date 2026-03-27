<?php

declare(strict_types=1);

namespace Src\Tenant\Actions;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Src\Tenant\Models\Tenant;

final class ListTenantsAction
{
    /** @return LengthAwarePaginator<int, Tenant> */
    public function handle(User $user, ?string $nameFilter): LengthAwarePaginator
    {
        $tenants = Tenant::query()
            ->join('tenant_user', 'tenants.id', '=', 'tenant_user.tenant_id')
            ->where('tenant_user.user_id', $user->id)
            ->when(
                $nameFilter !== null,
                fn ($q) => $q->whereRaw('LOWER(tenants.name) LIKE ?', ['%'.mb_strtolower((string) $nameFilter).'%'])
            )
            ->orderBy('tenants.name')
            ->select('tenants.*')
            ->paginate(10);

        $tableNames = config('permission.table_names');
        $tenantIds = $tenants->getCollection()->pluck('id')->all();

        $rolesByTenant = DB::table($tableNames['model_has_roles'].' as mhr')
            ->join($tableNames['roles'].' as r', 'r.id', '=', 'mhr.role_id')
            ->where('mhr.model_type', User::class)
            ->where('mhr.model_id', $user->id)
            ->whereIn('mhr.team_id', $tenantIds)
            ->pluck('r.name', 'mhr.team_id');

        $tenants->getCollection()->transform(function (Tenant $tenant) use ($rolesByTenant) {
            $tenant->setAttribute('role', $rolesByTenant->get($tenant->id));

            return $tenant;
        });

        return $tenants;
    }
}
