<?php

declare(strict_types=1);

namespace Src\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Src\Auth\Actions\Contracts\SyncUserTenantsActionInterface;
use Src\Shared\Services\Contracts\HuggyApiServiceInterface;
use Src\Tenant\Models\Tenant;

final class SyncUserTenantsAction implements SyncUserTenantsActionInterface
{
    public function __construct(private readonly HuggyApiServiceInterface $huggyApi) {}

    /**
     * Syncs the tenant list from Huggy with the local database and associates
     * the given user with all tenants they have access to.
     *
     * @return Collection<int, Tenant>
     */
    public function handle(User $user): Collection
    {
        $companies = $this->huggyApi
            ->withToken((string) $user->huggy_access_token)
            ->v4()
            ->getUserCompanies();

        if (empty($companies)) {
            return collect();
        }

        Tenant::upsert(
            array_map(fn (array $company): array => [
                'id' => $company['id'],
                'name' => $company['name'],
                'timezone' => $company['timezone'] ?? 'UTC',
            ], $companies),
            uniqueBy: ['id'],
            update: ['name', 'timezone'],
        );

        $tenantIds = array_column($companies, 'id');

        DB::connection('landlord')->table('tenant_user')
            ->where('user_id', $user->id)
            ->delete();

        DB::connection('landlord')->table('tenant_user')->insert(
            array_map(fn (int $tenantId): array => [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
            ], $tenantIds)
        );

        return Tenant::findMany($tenantIds);
    }
}
