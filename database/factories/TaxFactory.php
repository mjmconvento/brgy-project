<?php

namespace Database\Factories;

use App\Enums\TaxStatus;
use App\Models\Constituent;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Tax>
 */
class TaxFactory extends Factory
{
    /**
     * Rolling offset used to hand out a distinct billing period to every
     * generated record.
     */
    private static int $periodOffset = 0;

    /**
     * Define the model's default state.
     *
     * A random month/year pair would collide with the `taxes_period_unique`
     * index as soon as a factory produced two records, so periods are walked
     * backwards from the current month instead. Use `forPeriod()` when a test
     * needs a specific one.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $period = Carbon::now()->startOfMonth()->subMonths(self::$periodOffset++ % 600);

        return [
            'constituent_id' => Constituent::factory(),
            'amount' => fake()->randomFloat(2, 100, 10_000),
            'payment_month' => $period->month,
            'payment_year' => $period->year,
            'status' => fake()->randomElement(TaxStatus::cases()),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaxStatus::Paid,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TaxStatus::Unpaid,
        ]);
    }

    /**
     * Pin the record to a specific billing period, which the
     * `taxes_period_unique` index requires to be unique per constituent.
     */
    public function forPeriod(int $year, int $month): static
    {
        return $this->state(fn (array $attributes): array => [
            'payment_year' => $year,
            'payment_month' => $month,
        ]);
    }
}
