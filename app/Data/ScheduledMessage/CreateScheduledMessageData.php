<?php

declare(strict_types=1);

namespace App\Data\ScheduledMessage;

use Illuminate\Support\Carbon;

final class CreateScheduledMessageData
{
    public function __construct(
        public readonly int $providerConnectionId,
        public readonly string $contactId,
        public readonly string $contactName,
        public readonly string $message,
        public readonly Carbon $scheduledAt,
    ) {}
}
