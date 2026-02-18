<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ScheduledMessageStatus;
use App\Models\ProviderConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScheduledMessage>
 */
class ScheduledMessageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_connection_id' => ProviderConnection::factory(),
            'contact_id' => (string) fake()->randomNumber(5),
            'contact_name' => fake()->name(),
            'message' => fake()->sentence(),
            'scheduled_at' => now()->addHour(),
            'status' => ScheduledMessageStatus::PENDING->value,
            'attempts' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => ScheduledMessageStatus::PENDING->value]);
    }

    public function sent(): static
    {
        return $this->state(['status' => ScheduledMessageStatus::SENT->value]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ScheduledMessageStatus::FAILED->value,
            'attempts' => 3,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => ScheduledMessageStatus::CANCELLED->value]);
    }
}
