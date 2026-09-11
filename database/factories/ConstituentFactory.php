<?php

namespace Database\Factories;

use App\Models\BarangayCaptain;
use App\Models\Constituent;
use Database\Factories\Support\PhilippineAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Constituent>
 */
class ConstituentFactory extends Factory
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
            'barangay_captain_id' => BarangayCaptain::factory(),
        ];
    }

    /**
     * A constituent recorded without a middle name.
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

    /**
     * A constituent who did not vote for any captain.
     */
    public function withoutCaptain(): static
    {
        return $this->state(fn (array $attributes): array => [
            'barangay_captain_id' => null,
        ]);
    }

    /**
     * Attach the constituent to an existing captain.
     */
    public function for_captain(BarangayCaptain $captain): static
    {
        return $this->state(fn (array $attributes): array => [
            'barangay_captain_id' => $captain->id,
        ]);
    }
}
