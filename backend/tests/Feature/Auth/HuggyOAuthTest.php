<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Src\Auth\Actions\Contracts\SyncUserTenantsActionInterface;

function makeSocialiteUser(string $id = '42', string $name = 'João Silva', string $email = 'joao@empresa.com'): SocialiteUser
{
    $user = (new SocialiteUser)
        ->setRaw(['id' => $id, 'name' => $name, 'email' => $email])
        ->map(['id' => $id, 'name' => $name, 'email' => $email]);

    $user->token = 'access-token-abc';
    $user->refreshToken = 'refresh-token-xyz';
    $user->expiresIn = 2592000;

    return $user;
}

describe('Huggy OAuth redirect', function () {

    it('redirects to Huggy authorization URL', function () {
        Socialite::shouldReceive('driver->redirect')
            ->andReturn(redirect('https://auth.huggy.dev/oauth/authorize?response_type=code'));

        $this->get(route('auth.huggy.redirect'))
            ->assertRedirectContains('auth.huggy.dev/oauth/authorize');
    });

});

describe('Huggy OAuth callback', function () {

    beforeEach(function () {
        Socialite::shouldReceive('driver->user')->andReturn(makeSocialiteUser())->byDefault();

        $this->mock(SyncUserTenantsActionInterface::class)
            ->shouldReceive('handle')
            ->andReturn(collect());

        config(['app.frontend_url' => 'http://app.localhost.com']);
    });

    it('creates a new user on first login', function () {
        $this->get(route('auth.huggy.callback'));

        expect(User::count())->toBe(1);

        $user = User::first();
        expect($user->huggy_id)->toBe('42')
            ->and($user->name)->toBe('João Silva')
            ->and($user->email)->toBe('joao@empresa.com');
    });

    it('stores the access token and refresh token encrypted', function () {
        $this->get(route('auth.huggy.callback'));

        $user = User::first();

        expect($user->huggy_access_token)->toBe('access-token-abc')
            ->and($user->huggy_refresh_token)->toBe('refresh-token-xyz');

        $raw = Illuminate\Support\Facades\DB::table('users')
            ->where('id', $user->id)
            ->value('huggy_access_token');

        expect($raw)->not->toBe('access-token-abc');
    });

    it('stores the token expiry date', function () {
        $this->get(route('auth.huggy.callback'));

        expect(User::first()->huggy_token_expires_at)->not->toBeNull();
    });

    it('updates tokens when an existing user logs in again', function () {
        User::factory()->create([
            'huggy_id' => '42',
            'huggy_access_token' => 'old-token',
            'huggy_refresh_token' => 'old-refresh',
        ]);

        $updated = makeSocialiteUser();
        $updated->token = 'new-token';
        $updated->refreshToken = 'new-refresh';

        Socialite::shouldReceive('driver->user')->andReturn($updated);

        $this->get(route('auth.huggy.callback'));

        expect(User::count())->toBe(1);

        $user = User::first();
        expect($user->huggy_access_token)->toBe('new-token')
            ->and($user->huggy_refresh_token)->toBe('new-refresh');
    });

    it('creates a Sanctum token after successful login', function () {
        $this->get(route('auth.huggy.callback'));

        expect(PersonalAccessToken::count())->toBe(1);
        expect(PersonalAccessToken::first()->name)->toBe('huggy-oauth');
    });

    it('redirects to frontend with token after successful login', function () {
        $response = $this->get(route('auth.huggy.callback'));

        $response->assertRedirectContains('app.localhost.com/auth/callback?token=');
    });

});

describe('Logout', function () {

    it('revokes the current access token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('huggy-oauth')->plainTextToken;

        $this->withToken($token)
            ->postJson(route('auth.logout'))
            ->assertOk()
            ->assertJson(['message' => 'Logged out successfully']);

        expect(PersonalAccessToken::count())->toBe(0);
    });

    it('returns 401 when called without a token', function () {
        $this->postJson(route('auth.logout'))
            ->assertUnauthorized();
    });

});
