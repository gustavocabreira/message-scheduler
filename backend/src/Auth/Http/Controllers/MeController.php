<?php

declare(strict_types=1);

namespace Src\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Auth\Http\Resources\UserResource;

final class MeController extends Controller
{
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
