<?php

namespace Database\Seeders;

use App\Enums\TaxStatus;
use App\Models\BarangayCaptain;
use App\Models\Constituent;
use App\Models\CriminalRecord;
use App\Models\Tax;
use Database\Factories\Support\PhilippineAddress;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * The bulk data set: captains, their constituents, and every constituent's
 * tax and criminal history.
 *
 * Roughly 15,000 rows are written, so every stage buffers plain arrays and
 * bulk-inserts them in chunks instead of saving models one at a time. The
 * factories still define the attribute *shape* — each row starts from
 * `factory()->make()->getAttributes()` — but `insert()` bypasses Eloquent, so
 * timestamps are supplied here.
 */
class BarangaySeeder extends Seeder
{
    /**
     * Number of captains to generate.
     */
    private const CAPTAINS = 60;

    /**
     * Total constituents, including the unaffiliated ones below.
     */
    private const CONSTITUENTS = 3_200;

    /**
     * Residents who did not vote for any candidate.
     */
    private const UNAFFILIATED = 120;

    /**
     * Rows per `INSERT` statement.
     */
    private const CHUNK = 500;

    /**
     * Billing periods taxes may be drawn from, counting back from this month.
     */
    private const BILLING_PERIODS = 24;

    /**
     * Percentage of a captain's constituents living in the captain's own city.
     */
    private const HOME_CITY_SHARE = 80;

    /**
     * Percentage of a captain's constituents in the captain's own barangay.
     */
    private const HOME_BARANGAY_SHARE = 65;

    /**
     * One in this many constituents carries at least one unpaid tax record.
     */
    private const UNPAID_IN = 3;

    /**
     * One in this many constituents has a criminal record.
     */
    private const RECORDED_IN = 4;

    /**
     * The console driving the seed, when `db:seed` supplied one.
     */
    private ?Command $console = null;

    /**
     * Timestamp written to every bulk-inserted row.
     */
    private string $now = '';

    /**
     * Seed captains, constituents, taxes, and criminal records.
     */
    public function run(): void
    {
        // Without this the connection keeps all ~15,000 statements in memory.
        DB::connection()->disableQueryLog();

        /** @var Command|null $command */
        $command = $this->command;

        $this->console = $command;
        $this->now = Carbon::now()->toDateTimeString();

        DB::transaction(function (): void {
            $captains = $this->seedCaptains();
            $constituentIds = $this->seedConstituents($captains);

            $this->seedTaxes($constituentIds);
            $this->seedCriminalRecords($constituentIds);
        });
    }

    /**
     * @return Collection<int, BarangayCaptain>
     */
    private function seedCaptains(): Collection
    {
        $this->console?->info(sprintf('Seeding %d barangay captains...', self::CAPTAINS));

        $cities = PhilippineAddress::cities();
        $barangays = PhilippineAddress::barangays();
        $rows = [];

        foreach (BarangayCaptain::factory()->count(self::CAPTAINS)->make() as $captain) {
            $rows[] = [
                ...$captain->getAttributes(),
                // `PhilippineAddress::random()` is uniform; captains are
                // clustered instead so the dashboard's city, barangay and
                // "top captains" charts have visible peaks.
                'city' => $this->weighted($cities),
                'barangay' => $this->weighted($barangays),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];
        }

        $this->insertChunked(BarangayCaptain::class, $rows);

        return BarangayCaptain::query()
            ->orderByDesc('id')
            ->limit(self::CAPTAINS)
            ->get(['id', 'city', 'barangay']);
    }

    /**
     * @param  Collection<int, BarangayCaptain>  $captains
     * @return list<int>
     */
    private function seedConstituents(Collection $captains): array
    {
        $affiliated = self::CONSTITUENTS - self::UNAFFILIATED;
        $allocation = $this->skewedAllocation($affiliated, $captains->count());

        $this->console?->info(sprintf(
            'Seeding %s constituents (%s across captains, %s unaffiliated)...',
            number_format(self::CONSTITUENTS),
            number_format($affiliated),
            number_format(self::UNAFFILIATED),
        ));

        $cities = PhilippineAddress::cities();
        $barangays = PhilippineAddress::barangays();
        $bar = $this->progressBar(self::CONSTITUENTS);
        $buffer = [];

        foreach ($captains->values() as $index => $captain) {
            $count = $allocation[$index] ?? 0;

            if ($count < 1) {
                continue;
            }

            $made = Constituent::factory()
                ->count($count)
                ->make(['barangay_captain_id' => $captain->id]);

            foreach ($made as $constituent) {
                $buffer[] = [
                    ...$constituent->getAttributes(),
                    // Most of a captain's voters are neighbours of theirs.
                    'city' => random_int(1, 100) <= self::HOME_CITY_SHARE
                        ? $captain->city
                        : $this->weighted($cities),
                    'barangay' => random_int(1, 100) <= self::HOME_BARANGAY_SHARE
                        ? $captain->barangay
                        : $this->weighted($barangays),
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];

                $this->flush(Constituent::class, $buffer);
                $bar?->advance();
            }
        }

        $unaffiliated = Constituent::factory()
            ->count(self::UNAFFILIATED)
            ->withoutCaptain()
            ->make();

        foreach ($unaffiliated as $constituent) {
            $buffer[] = [
                ...$constituent->getAttributes(),
                'city' => $this->weighted($cities),
                'barangay' => $this->weighted($barangays),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $this->flush(Constituent::class, $buffer);
            $bar?->advance();
        }

        $this->flush(Constituent::class, $buffer, force: true);
        $this->finish($bar);

        /** @var list<int> $ids */
        $ids = Constituent::query()
            ->orderByDesc('id')
            ->limit(self::CONSTITUENTS)
            ->pluck('id')
            ->all();

        return $ids;
    }

    /**
     * Give every constituent two to five tax records in distinct periods.
     *
     * @param  list<int>  $constituentIds
     */
    private function seedTaxes(array $constituentIds): void
    {
        $this->console?->info('Seeding tax records (2-5 per constituent)...');

        $periods = $this->recentPeriods();
        $factory = Tax::factory();
        $bar = $this->progressBar(count($constituentIds));
        $buffer = [];
        $written = 0;

        foreach ($constituentIds as $constituentId) {
            $count = random_int(2, 5);

            // `array_rand()` hands back distinct keys, and the periods are
            // distinct months, so `taxes_period_unique` cannot be violated.
            // `shuffle()` then reindexes them, so the unpaid ones below are
            // not always the same periods.
            $keys = (array) array_rand($periods, $count);
            shuffle($keys);

            $unpaid = random_int(1, self::UNPAID_IN) === 1
                ? random_int(1, min(2, $count))
                : 0;

            foreach ($keys as $position => $key) {
                $period = $periods[$key];

                $buffer[] = [
                    ...$factory->make([
                        'constituent_id' => $constituentId,
                        // Wider than the factory default: 150 to 12,000 pesos.
                        'amount' => random_int(15_000, 1_200_000) / 100,
                        'payment_year' => $period['year'],
                        'payment_month' => $period['month'],
                        'status' => $position < $unpaid ? TaxStatus::Unpaid : TaxStatus::Paid,
                    ])->getAttributes(),
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];

                $this->flush(Tax::class, $buffer);
                $written++;
            }

            $bar?->advance();
        }

        $this->flush(Tax::class, $buffer, force: true);
        $this->finish($bar);

        $this->console?->info(sprintf('  %s tax records written.', number_format($written)));
    }

    /**
     * @param  list<int>  $constituentIds
     */
    private function seedCriminalRecords(array $constituentIds): void
    {
        $this->console?->info(sprintf(
            'Seeding criminal records (1 in %d constituents)...',
            self::RECORDED_IN,
        ));

        $factory = CriminalRecord::factory();
        $buffer = [];
        $written = 0;

        foreach ($constituentIds as $constituentId) {
            if (random_int(1, self::RECORDED_IN) !== 1) {
                continue;
            }

            // A repeat offender now and then, one case for most.
            $cases = random_int(1, 5) === 1 ? 2 : 1;

            for ($case = 0; $case < $cases; $case++) {
                $buffer[] = [
                    ...$factory->make(['constituent_id' => $constituentId])->getAttributes(),
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ];

                $this->flush(CriminalRecord::class, $buffer);
                $written++;
            }
        }

        $this->flush(CriminalRecord::class, $buffer, force: true);

        $this->console?->info(sprintf('  %s criminal records written.', number_format($written)));
    }

    /**
     * Insert the buffer once it is full, or when forced at the end of a stage.
     *
     * @param  class-string<Model>  $model
     * @param  list<array<string, mixed>>  $buffer
     */
    private function flush(string $model, array &$buffer, bool $force = false): void
    {
        if ($buffer === [] || (! $force && count($buffer) < self::CHUNK)) {
            return;
        }

        $this->insertChunked($model, $buffer);

        $buffer = [];
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertChunked(string $model, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $model::query()->insert($chunk);
        }
    }

    /**
     * The last two years of billing periods, newest first.
     *
     * @return list<array{year: int, month: int}>
     */
    private function recentPeriods(): array
    {
        $start = Carbon::now()->startOfMonth();
        $periods = [];

        for ($offset = 0; $offset < self::BILLING_PERIODS; $offset++) {
            $period = $start->copy()->subMonths($offset);

            $periods[] = ['year' => $period->year, 'month' => $period->month];
        }

        return $periods;
    }

    /**
     * Split a total across buckets on a 1/sqrt(rank) curve, so the busiest
     * captain carries roughly eight times the quietest one.
     *
     * @return list<int>
     */
    private function skewedAllocation(int $total, int $buckets): array
    {
        if ($buckets < 1) {
            return [];
        }

        $weights = [];

        for ($rank = 1; $rank <= $buckets; $rank++) {
            $weights[] = 1 / sqrt($rank);
        }

        $sum = array_sum($weights);
        $counts = [];

        foreach ($weights as $weight) {
            $counts[] = (int) floor($total * $weight / $sum);
        }

        // Hand the rounding remainder out one row at a time.
        for ($assigned = array_sum($counts), $index = 0; $assigned < $total; $assigned++, $index++) {
            $counts[$index % $buckets]++;
        }

        shuffle($counts);

        return $counts;
    }

    /**
     * Pick a value biased toward the front of the list.
     *
     * Squaring a uniform roll makes the first entries far more likely, which
     * is what turns the address charts into something worth plotting.
     *
     * @param  list<string>  $values
     */
    private function weighted(array $values): string
    {
        $roll = random_int(0, 9_999) / 10_000;
        $index = (int) ($roll ** 2 * count($values));

        return $values[min($index, count($values) - 1)];
    }

    private function progressBar(int $max): ?ProgressBar
    {
        return $this->console?->getOutput()->createProgressBar($max);
    }

    private function finish(?ProgressBar $bar): void
    {
        $bar?->finish();

        $this->console?->getOutput()->newLine();
    }
}
