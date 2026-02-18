<?php

declare(strict_types=1);

namespace App\Data\ScheduledMessage;

use Illuminate\Support\Carbon;

final class UpdateScheduledMessageData
{
    public function __construct(
        public readonly ?string $contactId,
        public readonly ?string $contactName,
        public readonly ?string $message,
        public readonly ?Carbon $scheduledAt,
    ) {}
}
