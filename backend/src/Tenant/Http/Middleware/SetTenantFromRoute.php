<?php

declare(strict_types=1);

namespace Src\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Src\Tenant\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

final class SetTenantFromRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $workspaceId = $request->route('workspace');
        $user = $request->user();

        if (! $workspaceId || ! $user) {
            abort(404);
        }

        $hasAccess = DB::connection('landlord')
            ->table('tenant_user')
            ->where('tenant_id', $workspaceId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $hasAccess) {
            abort(404);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->where('id', $workspaceId)->first();

        if (! $tenant) {
            abort(404);
        }

        $tenant->makeCurrent();

        return $next($request);
    }
}
