<?php

namespace Database\Factories;

use App\Models\SubContractor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubContractor>
 */
class SubContractorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_email' => fake()->unique()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
