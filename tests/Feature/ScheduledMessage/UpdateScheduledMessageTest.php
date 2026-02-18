<?php

declare(strict_types=1);

use App\Models\ScheduledMessage;
use App\Models\User;

describe('PUT /api/scheduled-messages/{id}', function (): void {
    it('updates a pending scheduled message', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/scheduled-messages/{$message->id}", [
            'message' => 'Updated message content',
            'scheduled_at' => now()->addDays(2)->toIso8601String(),
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Scheduled message updated successfully.']);

        $this->assertDatabaseHas('scheduled_messages', [
            'id' => $message->id,
            'message' => 'Updated message content',
        ]);
    });

    it('returns 422 when trying to update a sent message', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->sent()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')->putJson("/api/scheduled-messages/{$message->id}", [
            'message' => 'Updated',
        ])->assertStatus(422);
    });

    it('returns 403 for another users message', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->pending()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/scheduled-messages/{$message->id}", ['message' => 'x'])
            ->assertStatus(403);
    });

    it('validates scheduled_at must be in the future', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->pending()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')->putJson("/api/scheduled-messages/{$message->id}", [
            'scheduled_at' => now()->subHour()->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['scheduled_at']);
    });
});
