<?php

declare(strict_types=1);

namespace Src\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Src\Tenant\Http\Requests\ListTenantsRequest;
use Src\Tenant\Http\Resources\TenantResource;
use Src\Tenant\Models\Tenant;

final class TenantController extends Controller
{
    public function index(ListTenantsRequest $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $tenants = Tenant::query()
            ->join('tenant_user', 'tenants.id', '=', 'tenant_user.tenant_id')
            ->where('tenant_user.user_id', $user->id)
            ->when(
                $request->string('name')->isNotEmpty(),
                fn ($q) => $q->whereRaw('LOWER(tenants.name) LIKE ?', ['%'.mb_strtolower((string) $request->string('name')).'%'])
            )
            ->orderBy('tenants.name')
            ->select('tenants.*')
            ->paginate(10);

        return TenantResource::collection($tenants);
    }

    public function active(Request $request): TenantResource|JsonResponse
    {
        $tenantId = $request->session()->get('active_tenant_id')
            ?? $request->user()->refresh()->last_workspace_id;

        if (! $tenantId) {
            return response()->json(['message' => 'No active workspace.'], 404);
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $request->session()->forget('active_tenant_id');

            return response()->json(['message' => 'No active workspace.'], 404);
        }

        // Restaura a sessão a partir do banco para que requests subsequentes não precisem bater no banco
        $request->session()->put('active_tenant_id', $tenant->id);

        return new TenantResource($tenant);
    }

    public function activate(Request $request, int $workspace): TenantResource|JsonResponse
    {
        $hasAccess = DB::connection('landlord')
            ->table('tenant_user')
            ->where('tenant_id', $workspace)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $hasAccess) {
            return response()->json(['message' => 'Workspace not found.'], 404);
        }

        $tenant = Tenant::find($workspace);

        if (! $tenant) {
            return response()->json(['message' => 'Workspace not found.'], 404);
        }

        $request->session()->put('active_tenant_id', $tenant->id);
        $request->user()->update(['last_workspace_id' => $tenant->id]);

        return new TenantResource($tenant);
    }
}
