<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->randomElement(['Building A', 'Building B', 'Zone 1', 'Zone 2', 'Main Site', 'North Area', 'South Area']),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
