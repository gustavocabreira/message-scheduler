<?php

declare(strict_types=1);

use App\Enums\ScheduledMessageStatus;
use App\Models\ProviderConnection;
use App\Models\User;

describe('POST /api/scheduled-messages', function (): void {
    it('creates a scheduled message successfully', function (): void {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/scheduled-messages', [
            'provider_connection_id' => $connection->id,
            'contact_id' => 'contact-123',
            'contact_name' => 'John Doe',
            'message' => 'Hello, John!',
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'scheduled_message' => [
                    'id', 'contact_id', 'contact_name', 'message',
                    'scheduled_at', 'status', 'attempts',
                ],
            ])
            ->assertJson([
                'scheduled_message' => [
                    'status' => ScheduledMessageStatus::PENDING->value,
                    'attempts' => 0,
                ],
            ]);

        $this->assertDatabaseHas('scheduled_messages', [
            'user_id' => $user->id,
            'contact_id' => 'contact-123',
            'status' => ScheduledMessageStatus::PENDING->value,
        ]);
    });

    it('fails when scheduled_at is in the past', function (): void {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')->postJson('/api/scheduled-messages', [
            'provider_connection_id' => $connection->id,
            'contact_id' => 'contact-123',
            'contact_name' => 'John Doe',
            'message' => 'Hello!',
            'scheduled_at' => now()->subHour()->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);
    });

    it('fails without required fields', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/scheduled-messages', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'provider_connection_id', 'contact_id', 'contact_name', 'message', 'scheduled_at',
            ]);
    });

    it('fails with non-existent provider_connection_id', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/scheduled-messages', [
            'provider_connection_id' => 9999,
            'contact_id' => 'contact-123',
            'contact_name' => 'John Doe',
            'message' => 'Hello!',
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['provider_connection_id']);
    });

    it('returns 401 when unauthenticated', function (): void {
        $this->postJson('/api/scheduled-messages', [])->assertStatus(401);
    });
});
