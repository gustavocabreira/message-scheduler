<?php

declare(strict_types=1);

use App\Enums\ProviderStatus;
use App\Models\ProviderConnection;
use App\Models\User;

describe('Provider CRUD', function (): void {
    describe('GET /api/providers', function (): void {
        it('lists providers for the authenticated user only', function (): void {
            $user = User::factory()->create();
            ProviderConnection::factory()->create(['user_id' => $user->id]);
            ProviderConnection::factory()->create(); // another user's connection

            $response = $this->actingAs($user, 'sanctum')->getJson('/api/providers');

            $response->assertStatus(200)
                ->assertJsonCount(1, 'data');
        });

        it('returns 401 when unauthenticated', function (): void {
            $this->getJson('/api/providers')->assertStatus(401);
        });
    });

    describe('POST /api/providers', function (): void {
        it('creates a new provider connection', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user, 'sanctum')->postJson('/api/providers', [
                'provider_type' => 'huggy',
                'credentials' => json_encode(['access_token' => 'tok_test']),
            ]);

            $response->assertStatus(201)
                ->assertJsonStructure(['message', 'provider' => ['id', 'provider_type', 'status']]);

            $this->assertDatabaseHas('provider_connections', [
                'user_id' => $user->id,
                'provider_type' => 'huggy',
                'status' => ProviderStatus::INACTIVE->value,
            ]);
        });

        it('fails with invalid provider type', function (): void {
            $user = User::factory()->create();

            $this->actingAs($user, 'sanctum')->postJson('/api/providers', [
                'provider_type' => 'invalid-provider',
                'credentials' => '{}',
            ])->assertStatus(422)->assertJsonValidationErrors(['provider_type']);
        });

        it('fails without credentials', function (): void {
            $user = User::factory()->create();

            $this->actingAs($user, 'sanctum')->postJson('/api/providers', [
                'provider_type' => 'huggy',
            ])->assertStatus(422)->assertJsonValidationErrors(['credentials']);
        });
    });

    describe('GET /api/providers/{id}', function (): void {
        it('shows a provider connection owned by the user', function (): void {
            $user = User::factory()->create();
            $connection = ProviderConnection::factory()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user, 'sanctum')->getJson("/api/providers/{$connection->id}");

            $response->assertStatus(200)
                ->assertJsonStructure(['provider' => ['id', 'provider_type', 'status']]);
        });

        it('returns 403 for another users provider', function (): void {
            $user = User::factory()->create();
            $connection = ProviderConnection::factory()->create();

            $this->actingAs($user, 'sanctum')
                ->getJson("/api/providers/{$connection->id}")
                ->assertStatus(403);
        });
    });

    describe('PUT /api/providers/{id}', function (): void {
        it('updates provider credentials', function (): void {
            $user = User::factory()->create();
            $connection = ProviderConnection::factory()->create(['user_id' => $user->id]);
            $newCredentials = json_encode(['access_token' => 'new-token']);

            $response = $this->actingAs($user, 'sanctum')->putJson("/api/providers/{$connection->id}", [
                'credentials' => $newCredentials,
            ]);

            $response->assertStatus(200)->assertJson(['message' => 'Provider connection updated successfully.']);

            $connection->refresh();
            expect($connection->credentials)->toBe($newCredentials);
        });

        it('returns 403 for another users provider', function (): void {
            $user = User::factory()->create();
            $connection = ProviderConnection::factory()->create();

            $this->actingAs($user, 'sanctum')
                ->putJson("/api/providers/{$connection->id}", ['credentials' => '{}'])
                ->assertStatus(403);
        });
    });

    describe('DELETE /api/providers/{id}', function (): void {
        it('permanently deletes a provider connection', function (): void {
            $user = User::factory()->create();
            $connection = ProviderConnection::factory()->create(['user_id' => $user->id]);

            $response = $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/providers/{$connection->id}");

            $response->assertStatus(200);
            $this->assertDatabaseMissing('provider_connections', ['id' => $connection->id]);
        });

        it('returns 403 for another users provider', function (): void {
            $user = User::factory()->create();
            $connection = ProviderConnection::factory()->create();

            $this->actingAs($user, 'sanctum')
                ->deleteJson("/api/providers/{$connection->id}")
                ->assertStatus(403);
        });
    });
});
