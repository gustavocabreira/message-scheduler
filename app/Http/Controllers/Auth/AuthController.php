<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\RegisterUserAction;
use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        RegisterUserAction $action,
    ): JsonResponse {
        /** @var string $name */
        $name = $request->validated('name');
        /** @var string $email */
        $email = $request->validated('email');
        /** @var string $password */
        $password = $request->validated('password');
        /** @var string $timezone */
        $timezone = $request->validated('timezone', 'UTC');

        $data = new RegisterData($name, $email, $password, $timezone);
        $user = $action->execute($data);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(
        LoginRequest $request,
        LoginAction $action,
    ): JsonResponse {
        /** @var string $email */
        $email = $request->validated('email');
        /** @var string $password */
        $password = $request->validated('password');
        /** @var string $deviceName */
        $deviceName = $request->validated('device_name', 'api');

        try {
            $data = new LoginData($email, $password, $deviceName);
            $token = $action->execute($data);

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
