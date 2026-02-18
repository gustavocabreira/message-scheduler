<?php

declare(strict_types=1);

use App\Models\MessageLog;
use App\Models\ScheduledMessage;
use App\Models\User;

describe('GET /api/scheduled-messages', function (): void {
    it('lists only the authenticated users messages', function (): void {
        $user = User::factory()->create();
        ScheduledMessage::factory()->count(3)->create(['user_id' => $user->id]);
        ScheduledMessage::factory()->create(); // another user

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/scheduled-messages');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });

    it('filters by status', function (): void {
        $user = User::factory()->create();
        ScheduledMessage::factory()->pending()->count(2)->create(['user_id' => $user->id]);
        ScheduledMessage::factory()->sent()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/scheduled-messages?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('filters by contact_name', function (): void {
        $user = User::factory()->create();
        ScheduledMessage::factory()->create(['user_id' => $user->id, 'contact_name' => 'Alice Smith']);
        ScheduledMessage::factory()->create(['user_id' => $user->id, 'contact_name' => 'Bob Jones']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/scheduled-messages?contact_name=alice');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('returns 401 when unauthenticated', function (): void {
        $this->getJson('/api/scheduled-messages')->assertStatus(401);
    });
});

describe('GET /api/scheduled-messages/{id}/logs', function (): void {
    it('returns delivery logs for a message', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->sent()->create(['user_id' => $user->id]);
        MessageLog::factory()->count(2)->create(['scheduled_message_id' => $message->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/scheduled-messages/{$message->id}/logs");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'attempt', 'status', 'response', 'error_message', 'created_at']],
            ]);
    });

    it('returns 403 for another users message', function (): void {
        $user = User::factory()->create();
        $message = ScheduledMessage::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/scheduled-messages/{$message->id}/logs")
            ->assertStatus(403);
    });
});
