<?php

declare(strict_types=1);

use App\Enums\ProviderStatus;
use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

describe('POST /api/providers/{id}/test-connection', function (): void {
    it('returns connected true when provider responds successfully', function (): void {
        Http::fake([
            '*/v3/agents/profile' => Http::response(['id' => 1, 'name' => 'Test'], 200),
        ]);

        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/providers/{$connection->id}/test-connection");

        $response->assertStatus(200)
            ->assertJson(['connected' => true]);

        $this->assertDatabaseHas('provider_connections', [
            'id' => $connection->id,
            'status' => ProviderStatus::ACTIVE->value,
        ]);
    });

    it('returns connected false and sets error status when provider fails', function (): void {
        Http::fake([
            '*/v3/agents/profile' => Http::response([], 401),
        ]);

        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/providers/{$connection->id}/test-connection");

        $response->assertStatus(200)
            ->assertJson(['connected' => false]);

        $this->assertDatabaseHas('provider_connections', [
            'id' => $connection->id,
            'status' => ProviderStatus::ERROR->value,
        ]);
    });

    it('returns 403 for another users provider', function (): void {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/providers/{$connection->id}/test-connection")
            ->assertStatus(403);
    });
});
