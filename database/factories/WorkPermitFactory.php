<?php

namespace Database\Factories;

use App\Enums\PermitStatus;
use App\Models\PermitType;
use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkPermit>
 */
class WorkPermitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'permit_number' => 'PTW-'.date('Y').'-'.str_pad((string) fake()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'permit_type_id' => PermitType::factory(),
            'project_id' => Project::factory(),
            'sub_contractor_id' => null,
            'site_id' => Site::factory(),
            'engineer_id' => User::factory(),
            'supervisor_id' => User::factory(),
            'status' => PermitStatus::New,
            'location_area' => fake()->bothify('Area-##'),
            'location_floor' => fake()->randomElement(['Ground', 'Basement', '1st', '2nd', '3rd']),
            'location_description' => fake()->optional()->sentence(),
            'work_description' => fake()->paragraph(),
            'request_date' => fake()->date(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'shift' => fake()->randomElement(['Morning', 'Evening', 'Night']),
            'rejection_reason' => null,
        ];
    }

    public function newPermit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermitStatus::New,
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermitStatus::UnderReview,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermitStatus::Rejected,
            'rejection_reason' => fake()->sentence(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermitStatus::Approved,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PermitStatus::Active,
        ]);
    }
}
