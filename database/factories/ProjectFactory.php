<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Project',
            'description' => fake()->sentence(),
            'status' => 'active',
            'start_date' => fake()->date(),
            'end_date' => fake()->optional(0.7)->dateTimeBetween('now', '+1 year')?->format('Y-m-d'),
        ];
    }
}
