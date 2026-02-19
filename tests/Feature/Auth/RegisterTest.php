<?php

declare(strict_types=1);

use App\Models\User;

describe('POST /api/auth/register', function (): void {
    it('registers a new user successfully', function (): void {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'timezone', 'created_at'],
            ])
            ->assertJson([
                'user' => [
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'timezone' => 'UTC',
                ],
            ]);

        expect($response->json('token'))->not->toBeNull()->toBeString();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    });

    it('registers a user with custom timezone', function (): void {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'America/Sao_Paulo',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'user' => ['timezone' => 'America/Sao_Paulo'],
            ]);
    });

    it('fails with missing required fields', function (): void {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('fails with duplicate email', function (): void {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails with invalid email format', function (): void {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('fails when password confirmation does not match', function (): void {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('fails with invalid timezone', function (): void {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'Invalid/Timezone',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timezone']);
    });
});
