<?php

namespace Database\Factories;

use App\Enums\PacketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class PacketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tracking_code' => strtoupper(fake()->bothify('PKT-####??')),
            'recipient_name' => fake()->name(),
            'recipient_email' => fake()->safeEmail(),
            'destination_address' => fake()->address(),
            'weight_grams' => fake()->numberBetween(100, 50000),
            'status' => PacketStatus::CREATED,
        ];
    }

    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PacketStatus::IN_TRANSIT,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PacketStatus::DELIVERED,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PacketStatus::FAILED,
        ]);
    }
}
