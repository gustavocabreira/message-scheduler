<?php

declare(strict_types=1);

use Illuminate\Http\Request;
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

    it('returns null when no active_tenant_id in session', function () {
        $request = Request::create('/');
        $request->setLaravelSession(app('session')->driver('array'));

        $finder = new UserTenantFinder;

        expect($finder->findForRequest($request))->toBeNull();
    });

    it('returns the matching tenant when active_tenant_id is set in session', function () {
        $tenant = Tenant::create(['name' => 'Acme', 'timezone' => 'UTC']);

        $request = Request::create('/');
        $session = app('session')->driver('array');
        $session->put('active_tenant_id', $tenant->id);
        $request->setLaravelSession($session);

        $finder = new UserTenantFinder;
        $found = $finder->findForRequest($request);

        expect($found->id)->toBe($tenant->id);
    });

    it('returns null when active_tenant_id does not match any tenant', function () {
        $request = Request::create('/');
        $session = app('session')->driver('array');
        $session->put('active_tenant_id', 99999);
        $request->setLaravelSession($session);

        $finder = new UserTenantFinder;

        expect($finder->findForRequest($request))->toBeNull();
    });

});
