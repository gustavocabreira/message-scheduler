<?php

declare(strict_types=1);

use Src\Tenant\Models\Tenant;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Unit');

pest()->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/**
 * Run a closure in the context of a given tenant and restore
 * the previous tenant state afterwards.
 */
function asTenant(Tenant $tenant, Closure $callback): mixed
{
    return $tenant->execute($callback);
}
