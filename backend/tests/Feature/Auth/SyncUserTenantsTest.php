<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Src\Auth\Actions\SyncUserTenantsAction;
use Src\Shared\Exceptions\HuggyApiException;
use Src\Shared\Services\HuggyApiService;
use Src\Tenant\Models\Tenant;

function makeCompanies(): array
{
    return [
        ['id' => 1,      'name' => 'Huggy Testing', 'timezone' => 'America/Sao_Paulo', 'status' => true],
        ['id' => 14388,  'name' => 'Douglas',        'timezone' => 'America/Sao_Paulo', 'status' => true],
        ['id' => 313935, 'name' => "Gab's Company",  'timezone' => 'America/Bahia',     'status' => true],
    ];
}

/**
 * Mocks HuggyApiService to respond to the builder chain and return the given companies.
 *
 * @param  array<int, array<string, mixed>>  $companies
 */
function mockHuggyApiReturning(array $companies, string $expectedToken = 'token-abc'): void
{
    test()->mock(HuggyApiService::class)
        ->shouldReceive('withToken')->with($expectedToken)->andReturnSelf()
        ->shouldReceive('v4')->andReturnSelf()
        ->shouldReceive('getUserCompanies')->andReturn($companies);
}

describe('SyncUserTenantsAction', function () {

    it('creates tenants that do not exist yet', function () {
        $user = User::factory()->create(['huggy_access_token' => 'token-abc']);

        mockHuggyApiReturning(makeCompanies());

        app(SyncUserTenantsAction::class)->handle($user);

        expect(Tenant::count())->toBe(3);
    });

    it('updates existing tenants with fresh data from Huggy', function () {
        Tenant::create(['id' => 1, 'name' => 'Old Name', 'timezone' => 'UTC']);

        $user = User::factory()->create(['huggy_access_token' => 'token-abc']);

        mockHuggyApiReturning(makeCompanies());

        app(SyncUserTenantsAction::class)->handle($user);

        expect(Tenant::find(1)->name)->toBe('Huggy Testing')
            ->and(Tenant::find(1)->timezone)->toBe('America/Sao_Paulo');
    });

    it('associates the user with all synced tenants in the pivot', function () {
        $user = User::factory()->create(['huggy_access_token' => 'token-abc']);

        mockHuggyApiReturning(makeCompanies());

        app(SyncUserTenantsAction::class)->handle($user);

        $pivotTenantIds = DB::connection('landlord')
            ->table('tenant_user')
            ->where('user_id', $user->id)
            ->pluck('tenant_id')
            ->sort()
            ->values()
            ->all();

        expect($pivotTenantIds)->toBe([1, 14388, 313935]);
    });

    it('replaces previous pivot entries on re-sync', function () {
        $user = User::factory()->create(['huggy_access_token' => 'token-abc']);

        DB::connection('landlord')->table('tenant_user')->insert([
            ['tenant_id' => 999, 'user_id' => $user->id],
        ]);

        mockHuggyApiReturning(makeCompanies());

        app(SyncUserTenantsAction::class)->handle($user);

        $tenantIds = DB::connection('landlord')
            ->table('tenant_user')
            ->where('user_id', $user->id)
            ->pluck('tenant_id')
            ->all();

        expect($tenantIds)->not->toContain(999)
            ->and(count($tenantIds))->toBe(3);
    });

    it('returns the synced tenants as a collection', function () {
        $user = User::factory()->create(['huggy_access_token' => 'token-abc']);

        mockHuggyApiReturning(makeCompanies());

        $tenants = app(SyncUserTenantsAction::class)->handle($user);

        expect($tenants)->toHaveCount(3)
            ->and($tenants->pluck('id')->sort()->values()->all())->toBe([1, 14388, 313935]);
    });

    it('returns an empty collection when the API returns no companies', function () {
        $user = User::factory()->create(['huggy_access_token' => 'token-abc']);

        test()->mock(HuggyApiService::class)
            ->shouldReceive('withToken')->andReturnSelf()
            ->shouldReceive('v4')->andReturnSelf()
            ->shouldReceive('getUserCompanies')->andReturn([]);

        $tenants = app(SyncUserTenantsAction::class)->handle($user);

        expect($tenants)->toBeEmpty()
            ->and(Tenant::count())->toBe(0);
    });

    it('propagates HuggyApiException when the API call fails', function () {
        $user = User::factory()->create(['huggy_access_token' => 'token-abc']);

        test()->mock(HuggyApiService::class)
            ->shouldReceive('withToken')->andReturnSelf()
            ->shouldReceive('v4')->andReturnSelf()
            ->shouldReceive('getUserCompanies')->andThrow(new HuggyApiException('HTTP 401'));

        expect(fn () => app(SyncUserTenantsAction::class)->handle($user))
            ->toThrow(HuggyApiException::class);
    });

});
