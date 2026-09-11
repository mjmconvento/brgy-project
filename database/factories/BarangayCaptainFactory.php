<?php

namespace Database\Factories;

use App\Models\BarangayCaptain;
use Database\Factories\Support\PhilippineAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BarangayCaptain>
 */
class BarangayCaptainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'last_name' => fake()->lastName(),
            ...PhilippineAddress::random(),
        ];
    }

    /**
     * A captain recorded without a middle name.
     */
    public function withoutMiddleName(): static
    {
        return $this->state(fn (array $attributes): array => [
            'middle_name' => null,
        ]);
    }

    /**
     * An address with no house number, as rural and informal ones often are.
     */
    public function withoutHouseNumber(): static
    {
        return $this->state(fn (array $attributes): array => [
            'house_number' => null,
        ]);
    }
}
