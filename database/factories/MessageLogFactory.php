<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ScheduledMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MessageLog>
 */
class MessageLogFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'scheduled_message_id' => ScheduledMessage::factory(),
            'attempt' => 1,
            'status' => 'sent',
            'response' => json_encode(['success' => true]),
            'error_message' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state([
            'status' => 'failed',
            'response' => null,
            'error_message' => 'Connection timeout',
        ]);
    }
}
