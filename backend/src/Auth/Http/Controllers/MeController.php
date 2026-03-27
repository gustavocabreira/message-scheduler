<?php

declare(strict_types=1);

namespace Src\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Src\Auth\Http\Resources\UserResource;

#[Group(name: 'Auth')]
final class MeController extends Controller
{
    #[Endpoint(
        operationId: 'me',
        title: 'Authenticated user',
        description: 'Retorna os dados do usuário autenticado na sessão atual.',
    )]
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
