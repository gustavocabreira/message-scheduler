<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Src\Auth\Actions\SyncUserTenantsAction;
use Src\Tenant\Models\Tenant;

function makeSocialiteUser(string $id = '42', string $name = 'João Silva', string $email = 'joao@empresa.com'): SocialiteUser
{
    $user = (new SocialiteUser)
        ->setRaw(['id' => $id, 'name' => $name, 'email' => $email])
        ->map(['id' => $id, 'name' => $name, 'email' => $email]);

    $user->token        = 'access-token-abc';
    $user->refreshToken = 'refresh-token-xyz';
    $user->expiresIn    = 2592000;

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

        $this->mock(SyncUserTenantsAction::class)
            ->shouldReceive('handle')
            ->andReturn(collect());
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

        $raw = \Illuminate\Support\Facades\DB::table('users')
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
            'huggy_id'            => '42',
            'huggy_access_token'  => 'old-token',
            'huggy_refresh_token' => 'old-refresh',
        ]);

        $updated = makeSocialiteUser();
        $updated->token        = 'new-token';
        $updated->refreshToken = 'new-refresh';

        Socialite::shouldReceive('driver->user')->andReturn($updated);

        $this->get(route('auth.huggy.callback'));

        expect(User::count())->toBe(1);

        $user = User::first();
        expect($user->huggy_access_token)->toBe('new-token')
            ->and($user->huggy_refresh_token)->toBe('new-refresh');
    });

    it('authenticates the user after callback', function () {
        $this->get(route('auth.huggy.callback'));

        $this->assertAuthenticated();
    });

    it('sets active_tenant_id in session when sync returns tenants', function () {
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'UTC']);

        $this->mock(SyncUserTenantsAction::class)
            ->shouldReceive('handle')
            ->andReturn(collect([$tenant]));

        $this->get(route('auth.huggy.callback'))
            ->assertSessionHas('active_tenant_id', $tenant->id);
    });

    it('does not set active_tenant_id in session when sync returns no tenants', function () {
        $this->get(route('auth.huggy.callback'))
            ->assertSessionMissing('active_tenant_id');
    });

    it('redirects to home after successful login', function () {
        $this->get(route('auth.huggy.callback'))
            ->assertRedirect('/');
    });

});

describe('Logout', function () {

    it('logs the user out and invalidates the session', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['_token' => 'test-csrf'])
            ->post(route('auth.logout'), ['_token' => 'test-csrf'])
            ->assertRedirect('/');

        $this->assertGuest();
    });

});
