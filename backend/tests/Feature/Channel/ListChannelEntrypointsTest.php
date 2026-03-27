<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Src\Channel\Models\Channel;
use Src\Shared\Services\Contracts\HuggyApiServiceInterface;
use Src\Tenant\Models\Tenant;

describe('GET /v1/channels/{channel}/entrypoints', function () {

    it('returns entrypoints for the given channel from the Huggy API', function () {
        $user = User::factory()->create(['huggy_access_token' => 'test-token']);
        $tenant = Tenant::create(['name' => 'Acme Corp', 'timezone' => 'UTC']);
        $channel = Channel::query()->where('active', true)->firstOrFail();

        DB::connection('landlord')->table('tenant_user')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $entrypoints = [
            [
                'id' => 593,
                'name' => 'PowerZAP',
                'type' => 'Widget',
                'uuid' => '7e3ae233-f95b-11e8-8969-0ee2e7d4bad8',
                'provider' => 'huggy',
                'entrypoint' => 'https://huggy.dev/panel/preview/?token=abc123',
            ],
        ];

        $this->mock(HuggyApiServiceInterface::class)
            ->shouldReceive('withToken')->andReturnSelf()
            ->shouldReceive('v4')->andReturnSelf()
            ->shouldReceive('getChannelEntrypoints')->with($tenant->id, $channel->slug)->andReturn($entrypoints);

        $this->actingAs($user)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->getJson(route('channels.entrypoints', ['channel' => $channel->slug]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'type', 'uuid', 'provider', 'entrypoint']]])
            ->assertJsonPath('data.0.id', 593)
            ->assertJsonPath('data.0.name', 'PowerZAP')
            ->assertJsonPath('data.0.type', 'Widget');
    });

    it('returns the cached response on subsequent requests', function () {
        $user = User::factory()->create(['huggy_access_token' => 'test-token']);
        $tenant = Tenant::create(['name' => 'Acme Corp', 'timezone' => 'UTC']);
        $channel = Channel::query()->where('active', true)->firstOrFail();

        DB::connection('landlord')->table('tenant_user')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $entrypoints = [
            ['id' => 1, 'name' => 'Widget A', 'type' => 'Widget', 'uuid' => 'uuid-1', 'provider' => 'huggy', 'entrypoint' => 'https://huggy.dev/1'],
        ];

        // API should be called only once; second request uses cache
        $this->mock(HuggyApiServiceInterface::class)
            ->shouldReceive('withToken')->once()->andReturnSelf()
            ->shouldReceive('v4')->once()->andReturnSelf()
            ->shouldReceive('getChannelEntrypoints')->once()->andReturn($entrypoints);

        $routeParams = ['channel' => $channel->slug];

        $this->actingAs($user)->withSession(['active_tenant_id' => $tenant->id])->getJson(route('channels.entrypoints', $routeParams))->assertOk();
        $this->actingAs($user)->withSession(['active_tenant_id' => $tenant->id])->getJson(route('channels.entrypoints', $routeParams))->assertOk();
    });

    it('returns empty data when the Huggy API returns no entrypoints', function () {
        $user = User::factory()->create(['huggy_access_token' => 'test-token']);
        $tenant = Tenant::create(['name' => 'Acme Corp', 'timezone' => 'UTC']);
        $channel = Channel::query()->where('active', true)->firstOrFail();

        DB::connection('landlord')->table('tenant_user')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $this->mock(HuggyApiServiceInterface::class)
            ->shouldReceive('withToken')->andReturnSelf()
            ->shouldReceive('v4')->andReturnSelf()
            ->shouldReceive('getChannelEntrypoints')->andReturn([]);

        $this->actingAs($user)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->getJson(route('channels.entrypoints', ['channel' => $channel->slug]))
            ->assertOk()
            ->assertJsonPath('data', []);
    });

    it('returns 404 when the channel does not exist', function () {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Acme Corp', 'timezone' => 'UTC']);

        DB::connection('landlord')->table('tenant_user')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_tenant_id' => $tenant->id])
            ->getJson(route('channels.entrypoints', ['channel' => 'non-existent-slug']))
            ->assertNotFound();
    });

    it('returns 404 when the user has no active workspace in session', function () {
        $user = User::factory()->create();
        $channel = Channel::query()->where('active', true)->firstOrFail();

        $this->actingAs($user)
            ->getJson(route('channels.entrypoints', ['channel' => $channel->slug]))
            ->assertNotFound();
    });

    it('returns 401 when unauthenticated', function () {
        $channel = Channel::query()->where('active', true)->firstOrFail();

        $this->getJson(route('channels.entrypoints', ['channel' => $channel->slug]))
            ->assertUnauthorized();
    });

});
