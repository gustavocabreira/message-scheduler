<?php

declare(strict_types=1);

namespace Src\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Tenant\Actions\ActivateTenantAction;
use Src\Tenant\Actions\GetActiveTenantAction;
use Src\Tenant\Actions\ListTenantsAction;
use Src\Tenant\Http\Requests\ListTenantsRequest;
use Src\Tenant\Http\Resources\TenantResource;
use Src\Tenant\Models\Tenant;

final class TenantController extends Controller
{
    public function index(ListTenantsRequest $request, ListTenantsAction $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $nameFilter = $request->string('name')->isNotEmpty() ? (string) $request->string('name') : null;

        return TenantResource::collection($action->handle($user, $nameFilter));
    }

    public function active(Request $request, GetActiveTenantAction $action): TenantResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sessionTenantId = $request->session()->get('active_tenant_id');
        $tenant = $action->handle($user, $sessionTenantId);

        if (! $tenant instanceof Tenant) {
            if ($sessionTenantId) {
                $request->session()->forget('active_tenant_id');
            }

            return response()->json(['message' => 'No active workspace.'], 404);
        }

        // Restaura a sessão a partir do banco para que requests subsequentes não precisem bater no banco
        $request->session()->put('active_tenant_id', $tenant->id);

        return new TenantResource($tenant);
    }

    public function activate(Request $request, ActivateTenantAction $action, int $workspace): TenantResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $tenant = $action->handle($user, $workspace);

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Workspace not found.'], 404);
        }

        $request->session()->put('active_tenant_id', $tenant->id);

        return new TenantResource($tenant);
    }
}
