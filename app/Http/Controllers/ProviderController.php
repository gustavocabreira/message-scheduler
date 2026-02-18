<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Provider\CreateProviderConnectionAction;
use App\Actions\Provider\DeleteProviderConnectionAction;
use App\Actions\Provider\TestProviderConnectionAction;
use App\Actions\Provider\UpdateProviderConnectionAction;
use App\Data\Provider\CreateProviderData;
use App\Data\Provider\UpdateProviderData;
use App\Enums\ProviderType;
use App\Http\Requests\Provider\CreateProviderRequest;
use App\Http\Requests\Provider\UpdateProviderRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\ProviderConnectionResource;
use App\Models\ProviderConnection;
use App\Models\User;
use App\Services\ProviderFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class ProviderController extends Controller
{
    #[OA\Get(
        path: '/providers',
        summary: 'List all provider connections for the authenticated user',
        security: [['bearerAuth' => []]],
        tags: ['Providers'],
        responses: [
            new OA\Response(response: 200, description: 'List of provider connections'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();
        $connections = $user->providerConnections()->get();

        return ProviderConnectionResource::collection($connections);
    }

    #[OA\Post(
        path: '/providers',
        summary: 'Create a new provider connection',
        security: [['bearerAuth' => []]],
        tags: ['Providers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['provider_type', 'credentials'],
                properties: [
                    new OA\Property(property: 'provider_type', type: 'string', enum: ['huggy'], example: 'huggy'),
                    new OA\Property(property: 'credentials', type: 'string', example: '{"access_token":"tok_xxx"}'),
                    new OA\Property(property: 'settings', type: 'object', example: null),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Provider connection created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(
        CreateProviderRequest $request,
        CreateProviderConnectionAction $action,
    ): JsonResponse {
        /** @var string $providerTypeValue */
        $providerTypeValue = $request->validated('provider_type');
        /** @var string $credentials */
        $credentials = $request->validated('credentials');
        $rawSettings = $request->validated('settings');
        $settings = is_array($rawSettings) ? json_encode($rawSettings) : null;

        $providerType = ProviderType::from($providerTypeValue);
        $data = new CreateProviderData($providerType, $credentials, $settings !== false ? $settings : null);

        /** @var User $user */
        $user = $request->user();
        $connection = $action->execute($user, $data);

        return response()->json([
            'message' => 'Provider connection created successfully.',
            'provider' => new ProviderConnectionResource($connection),
        ], 201);
    }

    #[OA\Get(
        path: '/providers/{id}',
        summary: 'Get a specific provider connection',
        security: [['bearerAuth' => []]],
        tags: ['Providers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Provider connection details'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(Request $request, ProviderConnection $provider): JsonResponse
    {
        $this->authorizeOwnership($request, $provider);

        return response()->json([
            'provider' => new ProviderConnectionResource($provider),
        ]);
    }

    #[OA\Put(
        path: '/providers/{id}',
        summary: 'Update a provider connection credentials',
        security: [['bearerAuth' => []]],
        tags: ['Providers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['credentials'],
                properties: [
                    new OA\Property(property: 'credentials', type: 'string', example: '{"access_token":"tok_new"}'),
                    new OA\Property(property: 'settings', type: 'object', example: null),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Provider updated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function update(
        UpdateProviderRequest $request,
        ProviderConnection $provider,
        UpdateProviderConnectionAction $action,
    ): JsonResponse {
        $this->authorizeOwnership($request, $provider);

        /** @var string $credentials */
        $credentials = $request->validated('credentials');
        $rawSettings = $request->validated('settings');
        $settings = is_array($rawSettings) ? json_encode($rawSettings) : null;

        $data = new UpdateProviderData($credentials, $settings !== false ? $settings : null);
        $connection = $action->execute($provider, $data);

        return response()->json([
            'message' => 'Provider connection updated successfully.',
            'provider' => new ProviderConnectionResource($connection),
        ]);
    }

    #[OA\Delete(
        path: '/providers/{id}',
        summary: 'Delete a provider connection',
        security: [['bearerAuth' => []]],
        tags: ['Providers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Provider deleted'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function destroy(
        Request $request,
        ProviderConnection $provider,
        DeleteProviderConnectionAction $action,
    ): JsonResponse {
        $this->authorizeOwnership($request, $provider);
        $action->execute($provider);

        return response()->json([
            'message' => 'Provider connection deleted successfully.',
        ]);
    }

    #[OA\Post(
        path: '/providers/{id}/test-connection',
        summary: 'Test if a provider connection is still valid',
        security: [['bearerAuth' => []]],
        tags: ['Providers'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Connection test result'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function testConnection(
        Request $request,
        ProviderConnection $provider,
        TestProviderConnectionAction $action,
    ): JsonResponse {
        $this->authorizeOwnership($request, $provider);
        $isConnected = $action->execute($provider);

        return response()->json([
            'connected' => $isConnected,
            'provider' => new ProviderConnectionResource($provider->fresh() ?? $provider),
        ]);
    }

    #[OA\Get(
        path: '/providers/{id}/contacts',
        summary: 'List contacts from the provider',
        security: [['bearerAuth' => []]],
        tags: ['Providers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of contacts'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function contacts(
        Request $request,
        ProviderConnection $provider,
        ProviderFactory $factory,
    ): JsonResponse {
        $this->authorizeOwnership($request, $provider);

        /** @var array<string, string> $filters */
        $filters = array_filter([
            'search' => $request->query('search'),
        ]);

        /** @var ProviderType $providerType */
        $providerType = $provider->provider_type;
        $messageProvider = $factory->make($providerType, $provider);
        $contacts = $messageProvider->getContacts($filters);

        return response()->json([
            'data' => ContactResource::collection(collect($contacts)),
        ]);
    }

    private function authorizeOwnership(Request $request, ProviderConnection $provider): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($provider->user_id !== $user->id) {
            abort(403, 'This action is unauthorized.');
        }
    }
}
