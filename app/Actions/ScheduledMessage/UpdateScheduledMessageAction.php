<?php

declare(strict_types=1);

namespace App\Actions\ScheduledMessage;

use App\Data\ScheduledMessage\UpdateScheduledMessageData;
use App\Enums\ScheduledMessageStatus;
use App\Models\ScheduledMessage;
use RuntimeException;

class UpdateScheduledMessageAction
{
    public function execute(ScheduledMessage $scheduledMessage, UpdateScheduledMessageData $data): ScheduledMessage
    {
        /** @var ScheduledMessageStatus $status */
        $status = $scheduledMessage->status;

        if ($status !== ScheduledMessageStatus::PENDING) {
            throw new RuntimeException('Only pending messages can be updated.');
        }

        $updates = array_filter([
            'contact_id' => $data->contactId,
            'contact_name' => $data->contactName,
            'message' => $data->message,
            'scheduled_at' => $data->scheduledAt?->utc(),
        ], fn (mixed $value): bool => $value !== null);

        $scheduledMessage->update($updates);

        return $scheduledMessage->fresh() ?? $scheduledMessage;
    }
}
