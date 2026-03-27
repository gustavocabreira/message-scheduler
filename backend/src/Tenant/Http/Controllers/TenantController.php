<?php

declare(strict_types=1);

namespace Src\Tenant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Tenant\Actions\ActivateTenantAction;
use Src\Tenant\Actions\GetActiveTenantAction;
use Src\Tenant\Actions\ListTenantsAction;
use Src\Tenant\Http\Requests\ListTenantsRequest;
use Src\Tenant\Http\Resources\TenantResource;
use Src\Tenant\Models\Tenant;

#[Group(name: 'Workspaces')]
final class TenantController extends Controller
{
    #[Endpoint(
        operationId: 'workspaces.index',
        title: 'List workspaces',
        description: 'Retorna a lista paginada de workspaces aos quais o usuário autenticado pertence, ordenados alfabeticamente. Aceita filtro opcional por nome (busca parcial, case-insensitive).',
    )]
    public function index(ListTenantsRequest $request, ListTenantsAction $action): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $nameFilter = $request->string('name')->isNotEmpty() ? (string) $request->string('name') : null;

        return TenantResource::collection($action->handle($user, $nameFilter));
    }

    #[Endpoint(
        operationId: 'workspace.active',
        title: 'Get active workspace',
        description: 'Retorna o workspace atualmente ativo para o usuário autenticado. Utiliza a sessão corrente como fonte primária e, como fallback, o último workspace acessado persistido no banco.',
    )]
    #[Response(status: 404, description: 'Nenhum workspace ativo encontrado.')]
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

    #[Endpoint(
        operationId: 'workspaces.activate',
        title: 'Activate workspace',
        description: 'Define o workspace informado como ativo para a sessão corrente e persiste a escolha em last_workspace_id no perfil do usuário.',
    )]
    #[Response(status: 404, description: 'Workspace não encontrado ou sem acesso.')]
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
