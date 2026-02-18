<?php

declare(strict_types=1);

namespace App\Enums;

enum ScheduledMessageStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
