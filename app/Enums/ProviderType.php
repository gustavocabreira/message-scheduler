<?php

declare(strict_types=1);

namespace App\Enums;

enum ProviderType: string
{
    case HUGGY = 'huggy';

    public function label(): string
    {
        return match ($this) {
            ProviderType::HUGGY => 'Huggy',
        };
    }
}
