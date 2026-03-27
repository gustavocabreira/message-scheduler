<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Src\Auth\Actions\Contracts\SyncUserTenantsActionInterface;
use Src\Tenant\Models\Tenant;

function makeSocialiteUser(string $id = '42', string $name = 'João Silva', string $email = 'joao@empresa.com', ?string $avatar = null): SocialiteUser
{
    $raw = ['id' => $id, 'name' => $name, 'email' => $email];

    if ($avatar !== null) {
        $raw['photo'] = ['source' => $avatar];
    }

    $user = (new SocialiteUser)
        ->setRaw($raw)
        ->map(['id' => $id, 'name' => $name, 'email' => $email, 'avatar' => $avatar]);

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

    it('logs the user in after successful login', function () {
        $this->get(route('auth.huggy.callback'));

        $this->assertAuthenticated();
    });

    it('redirects to frontend after successful login', function () {
        $response = $this->get(route('auth.huggy.callback'));

        $response->assertRedirectContains('app.localhost.com/auth/callback');
    });

    it('does not set avatar_path when user has no avatar', function () {
        $this->get(route('auth.huggy.callback'));

        expect(User::first()->avatar_path)->toBeNull();
    });

});

describe('Huggy OAuth callback - default tenant', function () {

    beforeEach(function () {
        Socialite::shouldReceive('driver->user')->andReturn(makeSocialiteUser())->byDefault();
        config(['app.frontend_url' => 'http://app.localhost.com']);
    });

    it('sets the first tenant returned by the API as last_workspace_id on first login', function () {
        $tenantC = Tenant::create(['name' => 'Company C', 'timezone' => 'UTC']);
        $tenantA = Tenant::create(['name' => 'Company A', 'timezone' => 'UTC']);
        $tenantB = Tenant::create(['name' => 'Company B', 'timezone' => 'UTC']);

        $this->mock(SyncUserTenantsActionInterface::class)
            ->shouldReceive('handle')
            ->andReturn(collect([$tenantC, $tenantA, $tenantB]));

        $this->get(route('auth.huggy.callback'));

        expect(User::query()->first()->last_workspace_id)->toBe($tenantC->id);
    });

    it('does not overwrite last_workspace_id on subsequent login', function () {
        $tenantA = Tenant::create(['name' => 'Company A', 'timezone' => 'UTC']);
        $tenantB = Tenant::create(['name' => 'Company B', 'timezone' => 'UTC']);

        User::factory()->create(['huggy_id' => '42', 'last_workspace_id' => $tenantB->id]);

        $this->mock(SyncUserTenantsActionInterface::class)
            ->shouldReceive('handle')
            ->andReturn(collect([$tenantA, $tenantB]));

        $this->get(route('auth.huggy.callback'));

        expect(User::query()->first()->last_workspace_id)->toBe($tenantB->id);
    });

    it('does not fail and leaves last_workspace_id null when user has no tenants', function () {
        $this->mock(SyncUserTenantsActionInterface::class)
            ->shouldReceive('handle')
            ->andReturn(collect());

        $this->get(route('auth.huggy.callback'))
            ->assertRedirectContains('app.localhost.com');

        expect(User::query()->first()->last_workspace_id)->toBeNull();
    });

});

describe('Huggy OAuth callback - avatar', function () {

    beforeEach(function () {
        $this->mock(SyncUserTenantsActionInterface::class)
            ->shouldReceive('handle')
            ->andReturn(collect());

        config(['app.frontend_url' => 'http://app.localhost.com']);
    });

    it('downloads and stores the user avatar on login', function () {
        Storage::fake('public');
        Http::fake(['*' => Http::response('fake-image-content', 200)]);

        Socialite::shouldReceive('driver->user')
            ->andReturn(makeSocialiteUser(avatar: 'https://c.pzw.io/img/avatar-user-boy.jpg'));

        $this->get(route('auth.huggy.callback'));

        $user = User::first();
        expect($user->avatar_path)->not->toBeNull();
        Storage::disk('public')->assertExists($user->avatar_path);
    });

    it('stores avatar under avatars/{slugified-name}.{extension}', function () {
        Storage::fake('public');
        Http::fake(['*' => Http::response('fake-image-content', 200)]);

        Socialite::shouldReceive('driver->user')
            ->andReturn(makeSocialiteUser(name: 'João Silva', avatar: 'https://c.pzw.io/img/avatar-user-boy.jpg'));

        $this->get(route('auth.huggy.callback'));

        expect(User::first()->avatar_path)->toBe('avatars/joao-silva.jpg');
    });

    it('file is publicly accessible via the public disk', function () {
        Storage::fake('public');
        Http::fake(['*' => Http::response('fake-image-content', 200)]);

        Socialite::shouldReceive('driver->user')
            ->andReturn(makeSocialiteUser(avatar: 'https://c.pzw.io/img/avatar-user-boy.jpg'));

        $this->get(route('auth.huggy.callback'));

        $user = User::first();
        Storage::disk('public')->assertExists($user->avatar_path);
        expect(Storage::disk('public')->url($user->avatar_path))->toContain($user->avatar_path);
    });

    it('skips download when avatar file already exists', function () {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/joao-silva.jpg', 'existing-image-content');

        Http::fake(['*' => Http::response('new-image-content', 200)]);

        Socialite::shouldReceive('driver->user')
            ->andReturn(makeSocialiteUser(avatar: 'https://c.pzw.io/img/avatar-user-boy.jpg'));

        $this->get(route('auth.huggy.callback'));

        Http::assertNothingSent();
        expect(Storage::disk('public')->get('avatars/joao-silva.jpg'))->toBe('existing-image-content');
    });

    it('downloads avatar when file does not exist yet', function () {
        Storage::fake('public');
        Http::fake(['*' => Http::response('fresh-image-content', 200)]);

        Socialite::shouldReceive('driver->user')
            ->andReturn(makeSocialiteUser(avatar: 'https://c.pzw.io/img/avatar-user-boy.jpg'));

        $this->get(route('auth.huggy.callback'));

        Http::assertSentCount(1);
        expect(Storage::disk('public')->get('avatars/joao-silva.jpg'))->toBe('fresh-image-content');
    });

});
