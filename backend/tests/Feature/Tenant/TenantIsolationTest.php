<?php

declare(strict_types=1);

use Src\Tenant\Models\Tenant;

describe('Tenant isolation', function () {

    it('does not leak the current tenant between execute() calls', function () {
        $tenantA = Tenant::create(['name' => 'Tenant A', 'timezone' => 'UTC']);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'timezone' => 'UTC']);

        $tenantA->execute(function () use ($tenantB) {
            expect(Tenant::current()->id)->not->toBe($tenantB->id);

            $tenantB->execute(function () use ($tenantB) {
                expect(Tenant::current()->id)->toBe($tenantB->id);
            });

            // After tenantB's execute, tenantA must be current again
            expect(Tenant::current()->id)->toBe(Tenant::current()->id);
        });

        expect(Tenant::current())->toBeNull();
    });

    it('applies the correct timezone per tenant in isolated execute() calls', function () {
        $tenantA = Tenant::create(['name' => 'Tenant A', 'timezone' => 'America/Sao_Paulo']);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'timezone' => 'Asia/Tokyo']);

        $timezoneA = null;
        $timezoneB = null;

        $tenantA->execute(function () use (&$timezoneA) {
            $timezoneA = config('app.timezone');
        });

        $tenantB->execute(function () use (&$timezoneB) {
            $timezoneB = config('app.timezone');
        });

        expect($timezoneA)->toBe('America/Sao_Paulo')
            ->and($timezoneB)->toBe('Asia/Tokyo')
            ->and(config('app.timezone'))->toBe('UTC');
    });

    it('cannot see another tenant when no tenant is current', function () {
        Tenant::create(['name' => 'Tenant A', 'timezone' => 'UTC']);

        expect(Tenant::current())->toBeNull();
    });

});
