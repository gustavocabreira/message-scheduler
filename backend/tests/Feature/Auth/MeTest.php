<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Src\Tenant\Models\Tenant;

describe('GET /me', function () {

    it('returns the authenticated user data', function () {
        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@empresa.com',
            'huggy_id' => '42',
        ]);

        $this->actingAs($user)
            ->getJson(route('me'))
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'huggy_id', 'role', 'avatar_path', 'avatar_url', 'created_at']])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'João Silva')
            ->assertJsonPath('data.email', 'joao@empresa.com')
            ->assertJsonPath('data.huggy_id', '42');
    });

    it('returns the role for the active workspace', function () {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'UTC']);

        DB::connection('landlord')->table('tenant_user')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => 2,
            'model_type' => User::class,
            'model_id' => $user->id,
            'team_id' => $tenant->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->getJson(route('me'))
            ->assertOk()
            ->assertJsonPath('data.role', 'admin');
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(route('me'))
            ->assertUnauthorized();
    });

    it('does not expose sensitive fields', function () {
        $user = User::factory()->create([
            'huggy_access_token' => 'secret-access-token',
            'huggy_refresh_token' => 'secret-refresh-token',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('me'))
            ->assertOk();

        $data = $response->json('data');

        expect($data)->not->toHaveKey('password')
            ->and($data)->not->toHaveKey('huggy_access_token')
            ->and($data)->not->toHaveKey('huggy_refresh_token')
            ->and($data)->not->toHaveKey('remember_token');
    });

    it('returns created_at in ISO 8601 format', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('me'))
            ->assertOk();

        $createdAt = $response->json('data.created_at');

        expect($createdAt)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    });

    it('returns null for avatar fields when user has no avatar', function () {
        $user = User::factory()->create(['avatar_path' => null]);

        $response = $this->actingAs($user)
            ->getJson(route('me'))
            ->assertOk();

        expect($response->json('data.avatar_path'))->toBeNull()
            ->and($response->json('data.avatar_url'))->toBeNull();
    });

    it('returns avatar_path and avatar_url when user has an avatar', function () {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/joao-silva.jpg', 'fake-image-content');

        $user = User::factory()->create([
            'huggy_id' => '42',
            'avatar_path' => 'avatars/joao-silva.jpg',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('me'))
            ->assertOk();

        expect($response->json('data.avatar_path'))->toBe('avatars/joao-silva.jpg')
            ->and($response->json('data.avatar_url'))->toContain('avatars/joao-silva.jpg');
    });

});
