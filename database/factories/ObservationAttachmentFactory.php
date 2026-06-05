<?php

namespace Database\Factories;

use App\Models\Observation;
use App\Models\ObservationAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ObservationAttachment>
 */
class ObservationAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'observation_id' => Observation::factory(),
            'file_path' => 'observations/'.fake()->uuid().'.jpg',
            'file_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
        ];
    }
}
