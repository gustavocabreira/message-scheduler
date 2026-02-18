<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ScheduledMessage\CancelScheduledMessageAction;
use App\Actions\ScheduledMessage\CreateScheduledMessageAction;
use App\Actions\ScheduledMessage\UpdateScheduledMessageAction;
use App\Data\ScheduledMessage\CreateScheduledMessageData;
use App\Data\ScheduledMessage\UpdateScheduledMessageData;
use App\Http\Requests\ScheduledMessage\CreateScheduledMessageRequest;
use App\Http\Requests\ScheduledMessage\UpdateScheduledMessageRequest;
use App\Http\Resources\MessageLogResource;
use App\Http\Resources\ScheduledMessageResource;
use App\Models\ScheduledMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;
use RuntimeException;

class ScheduledMessageController extends Controller
{
    #[OA\Get(
        path: '/scheduled-messages',
        summary: 'List scheduled messages with optional filters',
        security: [['bearerAuth' => []]],
        tags: ['Scheduled Messages'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'processing', 'sent', 'failed', 'cancelled'])),
            new OA\Parameter(name: 'scheduled_at', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'provider_connection_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'contact_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated list of scheduled messages'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = $user->scheduledMessages()->with('providerConnection');

        if ($request->has('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->has('scheduled_at')) {
            $query->whereDate('scheduled_at', $request->string('scheduled_at')->value());
        }

        if ($request->has('provider_connection_id')) {
            $query->where('provider_connection_id', $request->integer('provider_connection_id'));
        }

        if ($request->has('contact_name')) {
            $query->where('contact_name', 'like', '%'.$request->string('contact_name')->value().'%');
        }

        $messages = $query->orderByDesc('scheduled_at')->paginate(20);

        return ScheduledMessageResource::collection($messages);
    }

    #[OA\Post(
        path: '/scheduled-messages',
        summary: 'Create a new scheduled message',
        security: [['bearerAuth' => []]],
        tags: ['Scheduled Messages'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['provider_connection_id', 'contact_id', 'contact_name', 'message', 'scheduled_at'],
                properties: [
                    new OA\Property(property: 'provider_connection_id', type: 'integer', example: 1),
                    new OA\Property(property: 'contact_id', type: 'string', example: 'contact-123'),
                    new OA\Property(property: 'contact_name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'message', type: 'string', example: 'Hello!'),
                    new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', example: '2026-03-01T10:00:00Z'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Scheduled message created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(
        CreateScheduledMessageRequest $request,
        CreateScheduledMessageAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        /** @var int $providerConnectionId */
        $providerConnectionId = $request->validated('provider_connection_id');
        /** @var string $contactId */
        $contactId = $request->validated('contact_id');
        /** @var string $contactName */
        $contactName = $request->validated('contact_name');
        /** @var string $message */
        $message = $request->validated('message');
        /** @var string $scheduledAtRaw */
        $scheduledAtRaw = $request->validated('scheduled_at');

        $data = new CreateScheduledMessageData(
            providerConnectionId: $providerConnectionId,
            contactId: $contactId,
            contactName: $contactName,
            message: $message,
            scheduledAt: Carbon::parse($scheduledAtRaw)->utc(),
        );

        $scheduledMessage = $action->execute($user, $data);

        return response()->json([
            'message' => 'Message scheduled successfully.',
            'scheduled_message' => new ScheduledMessageResource($scheduledMessage),
        ], 201);
    }

    #[OA\Get(
        path: '/scheduled-messages/{id}',
        summary: 'Get a specific scheduled message',
        security: [['bearerAuth' => []]],
        tags: ['Scheduled Messages'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Scheduled message details'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function show(Request $request, ScheduledMessage $scheduledMessage): JsonResponse
    {
        $this->authorizeOwnership($request, $scheduledMessage);

        return response()->json([
            'scheduled_message' => new ScheduledMessageResource($scheduledMessage),
        ]);
    }

    #[OA\Put(
        path: '/scheduled-messages/{id}',
        summary: 'Update a pending scheduled message',
        security: [['bearerAuth' => []]],
        tags: ['Scheduled Messages'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Message updated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Cannot update non-pending message'),
        ],
    )]
    public function update(
        UpdateScheduledMessageRequest $request,
        ScheduledMessage $scheduledMessage,
        UpdateScheduledMessageAction $action,
    ): JsonResponse {
        $this->authorizeOwnership($request, $scheduledMessage);

        /** @var string|null $contactId */
        $contactId = $request->validated('contact_id');
        /** @var string|null $contactName */
        $contactName = $request->validated('contact_name');
        /** @var string|null $message */
        $message = $request->validated('message');
        /** @var string|null $scheduledAtRaw */
        $scheduledAtRaw = $request->validated('scheduled_at');

        try {
            $data = new UpdateScheduledMessageData(
                contactId: $contactId,
                contactName: $contactName,
                message: $message,
                scheduledAt: $scheduledAtRaw !== null ? Carbon::parse($scheduledAtRaw)->utc() : null,
            );

            $updated = $action->execute($scheduledMessage, $data);

            return response()->json([
                'message' => 'Scheduled message updated successfully.',
                'scheduled_message' => new ScheduledMessageResource($updated),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Delete(
        path: '/scheduled-messages/{id}',
        summary: 'Cancel and soft-delete a scheduled message',
        security: [['bearerAuth' => []]],
        tags: ['Scheduled Messages'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Message cancelled'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function destroy(
        Request $request,
        ScheduledMessage $scheduledMessage,
        CancelScheduledMessageAction $action,
    ): JsonResponse {
        $this->authorizeOwnership($request, $scheduledMessage);

        try {
            $action->execute($scheduledMessage);
            $scheduledMessage->delete();

            return response()->json(['message' => 'Scheduled message cancelled successfully.']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Get(
        path: '/scheduled-messages/{id}/logs',
        summary: 'Get delivery logs for a scheduled message',
        security: [['bearerAuth' => []]],
        tags: ['Scheduled Messages'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Message delivery logs'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ],
    )]
    public function logs(Request $request, ScheduledMessage $scheduledMessage): JsonResponse
    {
        $this->authorizeOwnership($request, $scheduledMessage);

        $logs = $scheduledMessage->logs()->orderBy('created_at')->get();

        return response()->json([
            'data' => MessageLogResource::collection($logs),
        ]);
    }

    private function authorizeOwnership(Request $request, ScheduledMessage $scheduledMessage): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($scheduledMessage->user_id !== $user->id) {
            abort(403, 'This action is unauthorized.');
        }
    }
}
