<?php

declare(strict_types=1);

use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;

describe('GET /api/providers/{id}/contacts', function (): void {
    it('returns contacts from provider API', function (): void {
        Http::fake([
            '*/v3/contacts*' => Http::response([
                'data' => [
                    ['id' => '1', 'name' => 'Alice', 'phone' => '+5511999999999', 'email' => 'alice@example.com'],
                    ['id' => '2', 'name' => 'Bob', 'phone' => '+5511888888888', 'email' => 'bob@example.com'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/providers/{$connection->id}/contacts");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    });

    it('returns empty array when provider API fails', function (): void {
        Http::fake([
            '*/v3/contacts*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/providers/{$connection->id}/contacts");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('supports search filter', function (): void {
        Http::fake([
            '*/v3/contacts?search=alice*' => Http::response([
                'data' => [
                    ['id' => '1', 'name' => 'Alice', 'phone' => '+5511999999999', 'email' => 'alice@example.com'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/providers/{$connection->id}/contacts?search=alice");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    it('returns 403 for another users provider', function (): void {
        $user = User::factory()->create();
        $connection = ProviderConnection::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/providers/{$connection->id}/contacts")
            ->assertStatus(403);
    });
});
