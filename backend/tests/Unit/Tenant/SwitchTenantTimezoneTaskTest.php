<?php

use Spatie\Multitenancy\Contracts\IsTenant;
use Src\Tenant\Tasks\SwitchTenantTimezoneTask;

describe('SwitchTenantTimezoneTask', function () {

    it('applies the tenant timezone on makeCurrent', function () {
        $tenant = mock(IsTenant::class);
        $tenant->timezone = 'America/Sao_Paulo';

        $task = new SwitchTenantTimezoneTask;
        $task->makeCurrent($tenant);

        expect(config('app.timezone'))->toBe('America/Sao_Paulo')
            ->and(date_default_timezone_get())->toBe('America/Sao_Paulo');
    });

    it('restores UTC on forgetCurrent', function () {
        config(['app.timezone' => 'America/Sao_Paulo']);
        date_default_timezone_set('America/Sao_Paulo');

        $task = new SwitchTenantTimezoneTask;
        $task->forgetCurrent();

        expect(config('app.timezone'))->toBe('UTC')
            ->and(date_default_timezone_get())->toBe('UTC');
    });

    it('sets both config and PHP runtime timezone on makeCurrent', function () {
        $tenant = mock(IsTenant::class);
        $tenant->timezone = 'Europe/London';

        $task = new SwitchTenantTimezoneTask;
        $task->makeCurrent($tenant);

        // Both must be in sync — config alone is not enough for Carbon/now()
        expect(config('app.timezone'))->toBe(date_default_timezone_get());
    })->after(fn () => date_default_timezone_set('UTC'));

});
