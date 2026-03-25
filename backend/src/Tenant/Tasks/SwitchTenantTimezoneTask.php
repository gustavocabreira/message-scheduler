<?php

namespace Src\Tenant\Tasks;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class SwitchTenantTimezoneTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        config(['app.timezone' => $tenant->timezone]);
        date_default_timezone_set($tenant->timezone);
    }

    public function forgetCurrent(): void
    {
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
    }
}
