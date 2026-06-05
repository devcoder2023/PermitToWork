<?php

namespace Database\Factories;

use App\Enums\ObservationStatus;
use App\Models\Observation;
use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Observation>
 */
class ObservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'work_permit_id' => WorkPermit::factory(),
            'created_by' => User::factory(),
            'description' => fake()->paragraph(),
            'status' => ObservationStatus::Open,
            'resolution_note' => null,
            'rejection_reason' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ObservationStatus::Resolved,
            'resolution_note' => fake()->sentence(),
            'resolved_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ObservationStatus::Closed,
            'resolution_note' => fake()->sentence(),
            'resolved_at' => now(),
            'closed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ObservationStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
