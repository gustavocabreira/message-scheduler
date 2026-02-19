<?php

declare(strict_types=1);

use App\Enums\ProviderStatus;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

describe('Huggy OAuth2', function (): void {
    describe('GET /api/auth/huggy/redirect', function (): void {
        it('returns authorization URL for authenticated user', function (): void {
            $user = User::factory()->create();

            $mockProvider = Mockery::mock();
            $mockProvider->shouldReceive('stateless')->andReturnSelf();
            $mockProvider->shouldReceive('redirect')
                ->andReturn(new SymfonyRedirect('https://api.huggy.app/oauth/authorize?client_id=test'));

            Socialite::shouldReceive('driver')->with('huggy')->andReturn($mockProvider);

            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/auth/huggy/redirect');

            $response->assertStatus(200)
                ->assertJsonStructure(['authorization_url']);

            expect($response->json('authorization_url'))->toContain('oauth/authorize');
        });

        it('returns 401 when unauthenticated', function (): void {
            $this->getJson('/api/auth/huggy/redirect')->assertStatus(401);
        });
    });

    describe('GET /api/auth/huggy/callback', function (): void {
        it('exchanges code for tokens and stores them', function (): void {
            $user = User::factory()->create();

            $socialiteUser = new SocialiteUser;
            $socialiteUser->token = 'access-tok';
            $socialiteUser->refreshToken = 'refresh-tok';
            $socialiteUser->expiresIn = 3600;

            $mockProvider = Mockery::mock();
            $mockProvider->shouldReceive('stateless')->andReturnSelf();
            $mockProvider->shouldReceive('user')->andReturn($socialiteUser);

            Socialite::shouldReceive('driver')->with('huggy')->andReturn($mockProvider);

            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/auth/huggy/callback?code=valid-code');

            $response->assertStatus(200)
                ->assertJson(['message' => 'Huggy account connected successfully.']);

            $this->assertDatabaseHas('provider_connections', [
                'user_id' => $user->id,
                'status' => ProviderStatus::ACTIVE->value,
            ]);
        });

        it('returns 400 when no code is provided', function (): void {
            $user = User::factory()->create();

            $this->actingAs($user, 'sanctum')
                ->getJson('/api/auth/huggy/callback')
                ->assertStatus(400)
                ->assertJson(['message' => 'Authorization code is required.']);
        });

        it('returns 400 when token exchange fails', function (): void {
            $user = User::factory()->create();

            $mockProvider = Mockery::mock();
            $mockProvider->shouldReceive('stateless')->andReturnSelf();
            $mockProvider->shouldReceive('user')->andThrow(new \Exception('OAuth error'));

            Socialite::shouldReceive('driver')->with('huggy')->andReturn($mockProvider);

            $this->actingAs($user, 'sanctum')
                ->getJson('/api/auth/huggy/callback?code=bad-code')
                ->assertStatus(400)
                ->assertJson(['message' => 'Failed to exchange code for tokens.']);
        });

        it('returns 401 when unauthenticated', function (): void {
            $this->getJson('/api/auth/huggy/callback?code=xyz')->assertStatus(401);
        });
    });
});
