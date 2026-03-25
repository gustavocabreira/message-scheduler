<?php

declare(strict_types=1);

namespace Src\Tenant\Tasks;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;
use Src\Tenant\Models\Tenant;

final class SwitchTenantTimezoneTask implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        assert($tenant instanceof Tenant);
        config(['app.timezone' => $tenant->timezone]);
        date_default_timezone_set($tenant->timezone);
    }

    public function forgetCurrent(): void
    {
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
    }
}
