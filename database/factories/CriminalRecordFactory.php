<?php

namespace Database\Factories;

use App\Models\Constituent;
use App\Models\CriminalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CriminalRecord>
 */
class CriminalRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'constituent_id' => Constituent::factory(),
            'case_name' => fake()->randomElement([
                'Reckless driving',
                'Petty theft',
                'Public disturbance',
                'Illegal parking',
                'Vandalism',
                'Trespassing',
            ]),
            'details' => fake()->sentence(12),
            'occurred_at' => fake()->dateTimeBetween('-6 years', 'now'),
        ];
    }
}
