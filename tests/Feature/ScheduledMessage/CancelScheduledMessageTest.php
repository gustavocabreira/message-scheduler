<?php

declare(strict_types=1);

use App\Enums\ScheduledMessageStatus;
use App\Models\ScheduledMessage;
use App\Models\User;

describe('DELETE /api/scheduled-messages/{id}', function (): void {
    it('cancels and soft-deletes a pending message', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/scheduled-messages/{$message->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Scheduled message cancelled successfully.']);

        $this->assertSoftDeleted('scheduled_messages', ['id' => $message->id]);
        $this->assertDatabaseHas('scheduled_messages', [
            'id' => $message->id,
            'status' => ScheduledMessageStatus::CANCELLED->value,
        ]);
    });

    it('returns 422 when trying to cancel a sent message', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->sent()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/scheduled-messages/{$message->id}")
            ->assertStatus(422);
    });

    it('returns 403 for another users message', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->pending()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/scheduled-messages/{$message->id}")
            ->assertStatus(403);
    });
});
