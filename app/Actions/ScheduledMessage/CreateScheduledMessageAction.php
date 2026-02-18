<?php

declare(strict_types=1);

namespace App\Actions\ScheduledMessage;

use App\Data\ScheduledMessage\CreateScheduledMessageData;
use App\Enums\ScheduledMessageStatus;
use App\Models\ScheduledMessage;
use App\Models\User;

class CreateScheduledMessageAction
{
    public function execute(User $user, CreateScheduledMessageData $data): ScheduledMessage
    {
        /** @var ScheduledMessage */
        return $user->scheduledMessages()->create([
            'provider_connection_id' => $data->providerConnectionId,
            'contact_id' => $data->contactId,
            'contact_name' => $data->contactName,
            'message' => $data->message,
            'scheduled_at' => $data->scheduledAt->utc(),
            'status' => ScheduledMessageStatus::PENDING->value,
            'attempts' => 0,
        ]);
    }
}
