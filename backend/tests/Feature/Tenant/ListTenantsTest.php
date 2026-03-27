<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Src\Tenant\Models\Tenant;

describe('GET /v1/workspaces', function () {

    it('returns tenants sorted alphabetically', function () {
        $user = User::factory()->create();

        $beta = Tenant::create(['name' => 'Beta Corp',  'timezone' => 'UTC']);
        $alpha = Tenant::create(['name' => 'Alpha Corp', 'timezone' => 'UTC']);
        $gamma = Tenant::create(['name' => 'Gamma Corp', 'timezone' => 'UTC']);

        DB::connection('landlord')->table('tenant_user')->insert([
            ['tenant_id' => $beta->id,  'user_id' => $user->id],
            ['tenant_id' => $alpha->id, 'user_id' => $user->id],
            ['tenant_id' => $gamma->id, 'user_id' => $user->id],
        ]);

        $this->actingAs($user)
            ->getJson(route('workspaces.index'))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Alpha Corp')
            ->assertJsonPath('data.1.name', 'Beta Corp')
            ->assertJsonPath('data.2.name', 'Gamma Corp');
    });

    it('returns empty data when user has no tenants', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('workspaces.index'))
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);
    });

    it('does not return tenants belonging to other users', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $tenantA = Tenant::create(['name' => 'User A Tenant', 'timezone' => 'UTC']);
        $tenantB = Tenant::create(['name' => 'User B Tenant', 'timezone' => 'UTC']);

        DB::connection('landlord')->table('tenant_user')->insert([
            ['tenant_id' => $tenantA->id, 'user_id' => $userA->id],
            ['tenant_id' => $tenantB->id, 'user_id' => $userB->id],
        ]);

        $this->actingAs($userA)
            ->getJson(route('workspaces.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'User A Tenant');
    });

    it('paginates with 10 items per page', function () {
        $user = User::factory()->create();

        $tenants = collect(range(1, 15))->map(
            fn (int $i) => Tenant::create(['name' => "Tenant {$i}", 'timezone' => 'UTC'])
        );

        DB::connection('landlord')->table('tenant_user')->insert(
            $tenants->map(fn (Tenant $t) => ['tenant_id' => $t->id, 'user_id' => $user->id])->all()
        );

        $this->actingAs($user)
            ->getJson(route('workspaces.index'))
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(10, 'data');
    });

    it('returns the second page correctly', function () {
        $user = User::factory()->create();

        $tenants = collect(range(1, 12))->map(
            fn (int $i) => Tenant::create(['name' => "Workspace {$i}", 'timezone' => 'UTC'])
        );

        DB::connection('landlord')->table('tenant_user')->insert(
            $tenants->map(fn (Tenant $t) => ['tenant_id' => $t->id, 'user_id' => $user->id])->all()
        );

        $this->actingAs($user)
            ->getJson(route('workspaces.index').'?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters tenants by name (case-insensitive, partial match)', function () {
        $user = User::factory()->create();

        $acme = Tenant::create(['name' => 'Acme Corp',   'timezone' => 'UTC']);
        $huggy = Tenant::create(['name' => 'Huggy Inc',   'timezone' => 'UTC']);
        $acme2 = Tenant::create(['name' => 'Acme Brasil', 'timezone' => 'UTC']);

        DB::connection('landlord')->table('tenant_user')->insert([
            ['tenant_id' => $acme->id,  'user_id' => $user->id],
            ['tenant_id' => $huggy->id, 'user_id' => $user->id],
            ['tenant_id' => $acme2->id, 'user_id' => $user->id],
        ]);

        $this->actingAs($user)
            ->getJson(route('workspaces.index').'?name=acme')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Acme Brasil')
            ->assertJsonPath('data.1.name', 'Acme Corp');
    });

    it('returns empty data when name filter matches nothing', function () {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Huggy Inc', 'timezone' => 'UTC']);

        DB::connection('landlord')->table('tenant_user')->insert([
            ['tenant_id' => $tenant->id, 'user_id' => $user->id],
        ]);

        $this->actingAs($user)
            ->getJson(route('workspaces.index').'?name=nonexistent')
            ->assertOk()
            ->assertJsonPath('data', []);
    });

    it('returns 422 when name filter exceeds 255 characters', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('workspaces.index').'?name='.str_repeat('a', 256))
            ->assertUnprocessable();
    });

    it('returns 401 when unauthenticated', function () {
        $this->getJson(route('workspaces.index'))
            ->assertUnauthorized();
    });

    it('exposes only id and name fields', function () {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'America/Sao_Paulo']);

        DB::connection('landlord')->table('tenant_user')->insert([
            ['tenant_id' => $tenant->id, 'user_id' => $user->id],
        ]);

        $this->actingAs($user)
            ->getJson(route('workspaces.index'))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name']]])
            ->assertJsonMissingPath('data.0.timezone')
            ->assertJsonMissingPath('data.0.dispatch_window_start')
            ->assertJsonMissingPath('data.0.daily_dispatch_limit');
    });

});
