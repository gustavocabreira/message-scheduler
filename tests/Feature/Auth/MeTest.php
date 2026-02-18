<?php

declare(strict_types=1);

use App\Models\User;

describe('GET /api/auth/me', function (): void {
    it('returns the authenticated user profile', function (): void {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'timezone', 'created_at'],
            ])
            ->assertJson([
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'timezone' => 'America/Sao_Paulo',
                ],
            ]);
    });

    it('returns 401 without authentication', function (): void {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    });
});
