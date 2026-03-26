<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Src\Tenant\Models\Tenant;
use Src\Tenant\TenantFinder\UserTenantFinder;

describe('Tenant switching', function () {

    it('makes a tenant current and updates the timezone', function () {
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'America/Sao_Paulo']);

        $tenant->makeCurrent();

        expect(Tenant::current()->id)->toBe($tenant->id)
            ->and(config('app.timezone'))->toBe('America/Sao_Paulo');

        Tenant::forgetCurrent();
    });

    it('restores UTC and clears the current tenant on forgetCurrent', function () {
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'America/New_York']);

        $tenant->makeCurrent();
        Tenant::forgetCurrent();

        expect(Tenant::current())->toBeNull()
            ->and(config('app.timezone'))->toBe('UTC');
    });

    it('executes a callback in tenant context and restores state afterwards', function () {
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'Asia/Tokyo']);

        $capturedTimezone = null;

        $tenant->execute(function () use (&$capturedTimezone) {
            $capturedTimezone = config('app.timezone');
        });

        expect($capturedTimezone)->toBe('Asia/Tokyo')
            ->and(Tenant::current())->toBeNull()
            ->and(config('app.timezone'))->toBe('UTC');
    });

});

describe('UserTenantFinder', function () {

    function makeRequestWithWorkspace(mixed $workspaceId, ?User $user = null): Request
    {
        $request = Request::create('/');

        $stub = new class($workspaceId)
        {
            public function __construct(private readonly mixed $workspaceId) {}

            public function parameter(string $name, mixed $default = null): mixed
            {
                return $name === 'workspace' ? $this->workspaceId : $default;
            }
        };

        $request->setRouteResolver(fn () => $stub);

        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        return $request;
    }

    it('returns null when workspace param is missing', function () {
        $request = Request::create('/');
        $request->setRouteResolver(fn () => null);

        $finder = new UserTenantFinder;

        expect($finder->findForRequest($request))->toBeNull();
    });

    it('returns null when no authenticated user', function () {
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'UTC']);

        $request = makeRequestWithWorkspace($tenant->id);

        $finder = new UserTenantFinder;

        expect($finder->findForRequest($request))->toBeNull();
    });

    it('returns null when user does not belong to the workspace', function () {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'UTC']);

        $request = makeRequestWithWorkspace($tenant->id, $user);

        $finder = new UserTenantFinder;

        expect($finder->findForRequest($request))->toBeNull();
    });

    it('returns the tenant when workspace matches and user has access', function () {
        $user = User::factory()->create();
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'UTC']);

        DB::connection('landlord')->table('tenant_user')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);

        $request = makeRequestWithWorkspace($tenant->id, $user);

        $finder = new UserTenantFinder;
        $found = $finder->findForRequest($request);

        expect($found->id)->toBe($tenant->id);
    });

    it('returns null when workspace id does not match any tenant', function () {
        $user = User::factory()->create();

        $request = makeRequestWithWorkspace(99999, $user);

        $finder = new UserTenantFinder;

        expect($finder->findForRequest($request))->toBeNull();
    });

});
