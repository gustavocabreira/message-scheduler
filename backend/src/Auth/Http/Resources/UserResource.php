<?php

declare(strict_types=1);

namespace Src\Auth\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $this->resource->getAttributes();
        $lastWorkspaceId = $attributes['last_workspace_id'] ?? null;
        $activeTenantId = $request->session()->get('active_tenant_id') ?? $lastWorkspaceId;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'huggy_id' => $this->huggy_id,
            'role' => $activeTenantId !== null ? $this->resolveRoleForTenant((int) $activeTenantId) : null,
            'avatar_path' => $this->avatar_path,
            'avatar_url' => $this->avatar_path
                ? Storage::disk('public')->url($this->avatar_path)
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function resolveRoleForTenant(int $tenantId): ?string
    {
        return DB::table(config('permission.table_names.model_has_roles'))
            ->join(
                config('permission.table_names.roles'),
                config('permission.table_names.roles').'.id',
                '=',
                config('permission.table_names.model_has_roles').'.role_id'
            )
            ->where(config('permission.table_names.model_has_roles').'.model_type', User::class)
            ->where(config('permission.table_names.model_has_roles').'.model_id', $this->id)
            ->where(config('permission.table_names.model_has_roles').'.team_id', $tenantId)
            ->value(config('permission.table_names.roles').'.name');
    }
}
