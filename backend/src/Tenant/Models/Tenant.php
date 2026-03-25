<?php

declare(strict_types=1);

namespace Src\Tenant\Models;

use Spatie\Multitenancy\Models\Tenant as BaseTenant;

final class Tenant extends BaseTenant
{
    protected $fillable = [
        'name',
        'timezone',
        'dispatch_window_start',
        'dispatch_window_end',
        'daily_dispatch_limit',
        'min_cadence_minutes',
        'duplicate_window_hours',
    ];

    protected function casts(): array
    {
        return [
            'dispatch_window_start' => 'integer',
            'dispatch_window_end' => 'integer',
            'daily_dispatch_limit' => 'integer',
            'min_cadence_minutes' => 'integer',
            'duplicate_window_hours' => 'integer',
        ];
    }
}
