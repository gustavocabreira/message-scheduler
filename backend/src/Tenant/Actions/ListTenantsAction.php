<?php

declare(strict_types=1);

namespace Src\Tenant\Actions;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Src\Tenant\Models\Tenant;

final class ListTenantsAction
{
    /** @return LengthAwarePaginator<int, Tenant> */
    public function handle(User $user, ?string $nameFilter): LengthAwarePaginator
    {
        return Tenant::query()
            ->join('tenant_user', 'tenants.id', '=', 'tenant_user.tenant_id')
            ->where('tenant_user.user_id', $user->id)
            ->when(
                $nameFilter !== null,
                fn ($q) => $q->whereRaw('LOWER(tenants.name) LIKE ?', ['%'.mb_strtolower((string) $nameFilter).'%'])
            )
            ->orderBy('tenants.name')
            ->select('tenants.*')
            ->paginate(10);
    }
}
