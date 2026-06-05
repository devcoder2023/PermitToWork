<?php

namespace Database\Factories;

use App\Enums\DurationType;
use App\Models\PermitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermitType>
 */
class PermitTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name_en' => fake()->randomElement(['Cold Work', 'Hot Work', 'Electrical Work', 'Work at Height', 'Lifting Work']),
            'name_ar' => fake()->randomElement(['عمل بارد', 'عمل حار', 'عمل كهربائي', 'عمل في ارتفاع', 'عمل رفع']),
            'duration_type' => fake()->randomElement(DurationType::cases()),
            'is_active' => true,
        ];
    }
}
