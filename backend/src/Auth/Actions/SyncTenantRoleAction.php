<?php

declare(strict_types=1);

namespace Src\Auth\Actions;

use App\Models\User;
use Src\Shared\Services\Contracts\HuggyApiServiceInterface;
use Src\Tenant\Models\Tenant;

final class SyncTenantRoleAction
{
    public function __construct(
        private readonly HuggyApiServiceInterface $huggyApi,
    ) {}

    public function handle(User $user, Tenant $tenant): void
    {
        $huggyRole = $this->huggyApi
            ->withToken((string) $user->huggy_access_token)
            ->v4()
            ->getMyRoleInCompany($tenant->id);

        $roleName = $this->resolveRole($huggyRole);

        setPermissionsTeamId($tenant->id);

        try {
            $user->syncRoles([$roleName]);
        } finally {
            setPermissionsTeamId(null);
        }
    }

    /**
     * Maps a Huggy role response to a local system role name.
     *
     * Rules:
     * - id 1         → operator (permissions array is always empty for this id)
     * - id 2 or 3    → admin
     * - any other id → admin if permissions contain scope manage:all or manage:chat,
     *                  operator otherwise
     *
     * @param  array<string, mixed>  $huggyRole
     */
    private function resolveRole(array $huggyRole): string
    {
        $id = (int) $huggyRole['id'];

        if ($id === 2 || $id === 3) {
            return 'admin';
        }

        if ($id === 1) {
            return 'operator';
        }

        /** @var array<int, array<string, string>> $permissions */
        $permissions = $huggyRole['permissions'] ?? [];
        $scopes = array_column($permissions, 'scope');

        if (in_array('manage:all', $scopes, true) || in_array('manage:chat', $scopes, true)) {
            return 'admin';
        }

        return 'operator';
    }
}
