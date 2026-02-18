<?php

declare(strict_types=1);

use App\Models\User;

describe('POST /api/auth/logout', function (): void {
    it('logs out an authenticated user and revokes token', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    });

    it('returns 401 without authentication', function (): void {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    });
});
