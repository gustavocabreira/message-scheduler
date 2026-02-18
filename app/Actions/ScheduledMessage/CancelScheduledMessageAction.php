<?php

declare(strict_types=1);

namespace App\Actions\ScheduledMessage;

use App\Enums\ScheduledMessageStatus;
use App\Models\ScheduledMessage;
use RuntimeException;

class CancelScheduledMessageAction
{
    public function execute(ScheduledMessage $scheduledMessage): ScheduledMessage
    {
        /** @var ScheduledMessageStatus $status */
        $status = $scheduledMessage->status;

        if (! in_array($status, [ScheduledMessageStatus::PENDING, ScheduledMessageStatus::PROCESSING], true)) {
            throw new RuntimeException('Only pending or processing messages can be cancelled.');
        }

        $scheduledMessage->update(['status' => ScheduledMessageStatus::CANCELLED->value]);

        return $scheduledMessage->fresh() ?? $scheduledMessage;
    }
}
