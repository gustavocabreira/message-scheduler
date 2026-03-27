<?php

declare(strict_types=1);

namespace Src\Tenant\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Src\Tenant\Models\Tenant;

/** @mixin Tenant */
final class TenantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->resolveRole($request),
        ];
    }

    private function resolveRole(Request $request): ?string
    {
        /** @var array<string, mixed> $attributes */
        $attributes = $this->resource->getAttributes();
        $role = $attributes['role'] ?? null;

        if (is_string($role)) {
            return $role;
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return null;
        }

        return DB::table(config('permission.table_names.model_has_roles'))
            ->join(
                config('permission.table_names.roles'),
                config('permission.table_names.roles').'.id',
                '=',
                config('permission.table_names.model_has_roles').'.role_id'
            )
            ->where(config('permission.table_names.model_has_roles').'.model_type', User::class)
            ->where(config('permission.table_names.model_has_roles').'.model_id', $user->id)
            ->where(config('permission.table_names.model_has_roles').'.team_id', $this->id)
            ->value(config('permission.table_names.roles').'.name');
    }
}
