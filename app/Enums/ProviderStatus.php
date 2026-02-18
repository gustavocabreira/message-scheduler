<?php

declare(strict_types=1);

namespace App\Enums;

enum ProviderStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ERROR = 'error';
}
