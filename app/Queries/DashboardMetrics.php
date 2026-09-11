<?php

namespace App\Queries;

use App\Enums\TaxStatus;
use App\Models\BarangayCaptain;
use App\Models\Constituent;
use App\Models\CriminalRecord;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read model for the dashboard.
 *
 * Everything here is a grouped aggregate, so the page costs a fixed handful of
 * queries no matter how many constituents exist. The view receives plain arrays
 * and never touches the database.
 */
class DashboardMetrics
{
    /**
     * Billing periods shown on the monthly chart.
     */
    private const MONTHS = 12;

    /**
     * Rows kept in each "top N" breakdown.
     */
    private const BREAKDOWN_LIMIT = 8;

    /**
     * Recent criminal records listed on the dashboard.
     */
    private const RECENT_LIMIT = 5;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $taxesByStatus = $this->taxesByStatus();

        return [
            'totals' => $this->totals($taxesByStatus),
            'taxStatus' => [
                ['label' => TaxStatus::Paid->label(), 'total' => $taxesByStatus[TaxStatus::Paid->value]['records']],
                ['label' => TaxStatus::Unpaid->label(), 'total' => $taxesByStatus[TaxStatus::Unpaid->value]['records']],
            ],
            'monthlyTaxes' => $this->monthlyTaxes(),
            'outstandingByBarangay' => $this->outstandingByBarangay(),
            'constituentsByCity' => $this->constituentsByCity(),
            'topCaptains' => $this->topCaptains(),
            'recentRecords' => $this->recentRecords(),
        ];
    }

    /**
     * Record count and peso total per tax status, in one grouped query.
     *
     * @return array<string, array{records: int, total: float}>
     */
    private function taxesByStatus(): array
    {
        $rows = Tax::query()
            ->selectRaw('status, COUNT(*) as record_count, COALESCE(SUM(amount), 0) as amount_total')
            ->groupBy('status')
            ->get()
            ->keyBy(fn (Tax $tax): string => $tax->status->value);

        $byStatus = [];

        foreach (TaxStatus::cases() as $status) {
            $row = $rows->get($status->value);

            $byStatus[$status->value] = [
                'records' => (int) ($row?->getAttribute('record_count') ?? 0),
                'total' => (float) ($row?->getAttribute('amount_total') ?? 0),
            ];
        }

        return $byStatus;
    }

    /**
     * @param  array<string, array{records: int, total: float}>  $taxesByStatus
     * @return array<string, int|float>
     */
    private function totals(array $taxesByStatus): array
    {
        return [
            'constituents' => Constituent::query()->count(),
            'captains' => BarangayCaptain::query()->count(),
            'criminalRecords' => CriminalRecord::query()->count(),
            'withUnpaidTaxes' => Constituent::query()
                ->whereHas('taxes', fn (Builder $query) => $query->where('status', TaxStatus::Unpaid))
                ->count(),
            'withCriminalRecord' => Constituent::query()->has('criminalRecords')->count(),
            'outstandingTotal' => $taxesByStatus[TaxStatus::Unpaid->value]['total'],
            'collectedTotal' => $taxesByStatus[TaxStatus::Paid->value]['total'],
        ];
    }

    /**
     * Paid vs unpaid pesos for each of the last twelve billing periods,
     * oldest first. Periods with no data are still emitted as zeroes so the
     * chart keeps an even x-axis.
     *
     * @return list<array{label: string, paid: float, unpaid: float}>
     */
    private function monthlyTaxes(): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(self::MONTHS - 1);

        // Periods are stored as separate year/month integers; comparing them as
        // a single ordinal keeps the range filter index-friendly and correct
        // across a year boundary.
        $startOrdinal = ($start->year * 12) + $start->month;

        $buckets = [];
        $period = $start->copy();

        for ($index = 0; $index < self::MONTHS; $index++) {
            $buckets[($period->year * 12) + $period->month] = [
                'label' => $period->format('M Y'),
                'paid' => 0.0,
                'unpaid' => 0.0,
            ];

            $period->addMonth();
        }

        $rows = Tax::query()
            ->selectRaw('payment_year, payment_month, status, COALESCE(SUM(amount), 0) as total')
            ->whereRaw('((payment_year * 12) + payment_month) >= ?', [$startOrdinal])
            ->groupBy('payment_year', 'payment_month', 'status')
            ->get();

        foreach ($rows as $row) {
            $ordinal = ((int) $row->payment_year * 12) + (int) $row->payment_month;

            if (! isset($buckets[$ordinal])) {
                continue;
            }

            $key = $row->status === TaxStatus::Paid ? 'paid' : 'unpaid';
            $buckets[$ordinal][$key] = (float) $row->getAttribute('total');
        }

        return array_values($buckets);
    }

    /**
     * @return list<array{label: string, total: float}>
     */
    private function outstandingByBarangay(): array
    {
        return DB::table('taxes')
            ->join('constituents', 'constituents.id', '=', 'taxes.constituent_id')
            ->where('taxes.status', TaxStatus::Unpaid->value)
            ->selectRaw('constituents.barangay as label, SUM(taxes.amount) as total')
            ->groupBy('constituents.barangay')
            ->orderByDesc('total')
            ->limit(self::BREAKDOWN_LIMIT)
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'total' => (float) $row->total,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, total: int}>
     */
    private function constituentsByCity(): array
    {
        return DB::table('constituents')
            ->selectRaw('city as label, COUNT(*) as total')
            ->groupBy('city')
            ->orderByDesc('total')
            ->limit(self::BREAKDOWN_LIMIT)
            ->get()
            ->map(fn (object $row): array => [
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, total: int}>
     */
    private function topCaptains(): array
    {
        return BarangayCaptain::query()
            ->withCount('constituents')
            // `has()` rather than `having('constituents_count', '>', 0)`: a
            // HAVING clause without GROUP BY is a MySQL extension. PostgreSQL
            // treats such a query as an aggregate and rejects it, SQLite
            // refuses it outright, and the EXISTS subquery this emits can use
            // the `barangay_captain_id` index instead of filtering a computed
            // alias.
            ->has('constituents')
            ->orderByDesc('constituents_count')
            ->limit(self::BREAKDOWN_LIMIT)
            ->get()
            ->map(fn (BarangayCaptain $captain): array => [
                'label' => $captain->full_name,
                'total' => (int) $captain->constituents_count,
            ])
            ->all();
    }

    /**
     * @return list<array{case_name: string, occurred_at: string, constituent: string, url: string}>
     */
    private function recentRecords(): array
    {
        return CriminalRecord::query()
            ->with('constituent')
            ->latestFirst()
            ->limit(self::RECENT_LIMIT)
            ->get()
            ->map(fn (CriminalRecord $record): array => [
                'case_name' => $record->case_name,
                'occurred_at' => $record->occurred_at->format('M j, Y'),
                'constituent' => $record->constituent->full_name,
                'url' => route('constituents.show', $record->constituent),
            ])
            ->all();
    }
}
