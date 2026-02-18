<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProviderStatus;
use App\Enums\ProviderType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProviderConnection>
 */
class ProviderConnectionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider_type' => ProviderType::HUGGY->value,
            'status' => ProviderStatus::INACTIVE->value,
            'credentials' => json_encode(['access_token' => 'fake-token']),
            'settings' => null,
            'connected_at' => null,
            'last_synced_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProviderStatus::ACTIVE->value,
            'connected_at' => now(),
        ]);
    }
}
