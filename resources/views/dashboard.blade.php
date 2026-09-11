@php
    /** @var array<string, mixed> $metrics */
    $totals = $metrics['totals'];

    $peso = static fn (float|int $amount): string => '₱'.number_format((float) $amount, 2);

    $paidColor = '#16a34a';
    $unpaidColor = '#dc2626';
    $brand = '#1d5a74';
    $brandSoft = '#45a6c3';

    $monthly = $metrics['monthlyTaxes'];
    $taxStatus = $metrics['taxStatus'];
    $byBarangay = $metrics['outstandingByBarangay'];
    $byCity = $metrics['constituentsByCity'];
    $captainRanking = $metrics['topCaptains'];

    $hasTaxStatus = array_sum(array_column($taxStatus, 'total')) > 0;

    $noLegend = ['plugins' => ['legend' => ['display' => false]]];

    $verticalBarOptions = $noLegend + [
        'scales' => [
            'y' => ['beginAtZero' => true],
            'x' => ['grid' => ['display' => false]],
        ],
    ];

    $horizontalBarOptions = $noLegend + [
        'indexAxis' => 'y',
        'scales' => [
            'x' => ['beginAtZero' => true],
            'y' => ['grid' => ['display' => false]],
        ],
    ];

    $monthlyChart = [
        'labels' => array_column($monthly, 'label'),
        'datasets' => [
            [
                'label' => 'Paid',
                'data' => array_column($monthly, 'paid'),
                'borderColor' => $paidColor,
                'backgroundColor' => 'rgba(22, 163, 74, 0.14)',
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 2,
                'borderWidth' => 2,
            ],
            [
                'label' => 'Unpaid',
                'data' => array_column($monthly, 'unpaid'),
                'borderColor' => $unpaidColor,
                'backgroundColor' => $unpaidColor,
                // Only the Paid series is filled; two stacked translucent areas muddy each other.
                'fill' => false,
                'tension' => 0.35,
                'pointRadius' => 2,
                'borderWidth' => 2,
            ],
        ],
    ];

    $monthlyOptions = [
        'interaction' => ['mode' => 'index', 'intersect' => false],
        'plugins' => ['legend' => ['position' => 'bottom']],
        'scales' => [
            'y' => ['beginAtZero' => true],
            'x' => ['grid' => ['display' => false]],
        ],
    ];

    $statusChart = [
        'labels' => array_column($taxStatus, 'label'),
        'datasets' => [[
            'data' => array_column($taxStatus, 'total'),
            'backgroundColor' => array_map(
                static fn (array $slice): string => $slice['label'] === 'Paid' ? $paidColor : $unpaidColor,
                $taxStatus,
            ),
            'borderWidth' => 0,
        ]],
    ];

    $statusOptions = [
        'cutout' => '62%',
        'plugins' => ['legend' => ['position' => 'bottom']],
    ];

    $barangayChart = [
        'labels' => array_column($byBarangay, 'label'),
        'datasets' => [[
            'label' => 'Amount owed',
            'data' => array_column($byBarangay, 'total'),
            'backgroundColor' => $unpaidColor,
            'borderRadius' => 4,
        ]],
    ];

    $cityChart = [
        'labels' => array_column($byCity, 'label'),
        'datasets' => [[
            'label' => 'Residents',
            'data' => array_column($byCity, 'total'),
            'backgroundColor' => $brand,
            'borderRadius' => 4,
        ]],
    ];

    $captainChart = [
        'labels' => array_column($captainRanking, 'label'),
        'datasets' => [[
            'label' => 'Voters',
            'data' => array_column($captainRanking, 'total'),
            'backgroundColor' => $brandSoft,
            'borderRadius' => 4,
        ]],
    ];
@endphp

<x-layouts.app title="Dashboard">
    <x-slot:header>
        <x-ui.page-header
            title="Dashboard"
            subtitle="At-a-glance figures for the barangay profiling system."
        />
    </x-slot:header>

    <div class="space-y-6">
        {{-- Stat cards --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            <x-ui.stat-card
                label="Constituents"
                :value="number_format($totals['constituents'])"
                hint="registered residents"
            />

            <x-ui.stat-card
                label="Outstanding taxes"
                :value="$peso($totals['outstandingTotal'])"
                tone="negative"
                :hint="number_format($totals['withUnpaidTaxes']).' residents owing'"
            />

            <x-ui.stat-card
                label="Collected taxes"
                :value="$peso($totals['collectedTotal'])"
                tone="positive"
                hint="settled tax records"
            />

            <x-ui.stat-card
                label="Criminal records"
                :value="number_format($totals['criminalRecords'])"
                :hint="number_format($totals['withCriminalRecord']).' residents affected'"
            />

            <x-ui.stat-card
                label="Barangay captains"
                :value="number_format($totals['captains'])"
                hint="candidates on file"
            />
        </div>

        {{-- Charts --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <x-ui.card title="Tax billing over the last 12 periods">
                @if (filled($monthly))
                    <x-ui.chart type="line" :data="$monthlyChart" :options="$monthlyOptions" />
                @else
                    <x-ui.empty-state message="No tax records to chart yet." />
                @endif
            </x-ui.card>

            <x-ui.card title="Tax records by status">
                @if ($hasTaxStatus)
                    <x-ui.chart type="doughnut" :data="$statusChart" :options="$statusOptions" />
                @else
                    <x-ui.empty-state message="No tax records to chart yet." />
                @endif
            </x-ui.card>

            <x-ui.card title="Outstanding tax by barangay">
                @if (filled($byBarangay))
                    <x-ui.chart type="bar" :data="$barangayChart" :options="$horizontalBarOptions" />
                @else
                    <x-ui.empty-state message="Nothing outstanding — no barangay owes tax." />
                @endif
            </x-ui.card>

            <x-ui.card title="Constituents by city">
                @if (filled($byCity))
                    <x-ui.chart type="bar" :data="$cityChart" :options="$verticalBarOptions" />
                @else
                    <x-ui.empty-state message="No constituents recorded yet." />
                @endif
            </x-ui.card>

            <x-ui.card title="Votes per barangay captain" class="lg:col-span-2">
                @if (filled($captainRanking))
                    <x-ui.chart type="bar" :data="$captainChart" :options="$horizontalBarOptions" :height="320" />
                @else
                    <x-ui.empty-state message="No barangay captains recorded yet." />
                @endif
            </x-ui.card>
        </div>

        {{-- Recent criminal records --}}
        <div class="card">
            <div class="border-b border-slate-200 px-4 py-3 sm:px-6">
                <h2 class="text-sm font-semibold text-slate-900">Recent criminal records</h2>
            </div>

            @if (filled($metrics['recentRecords']))
                <x-ui.table>
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 sm:px-6">Case</th>
                            <th scope="col" class="px-4 py-3">Occurred</th>
                            <th scope="col" class="px-4 py-3">Constituent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($metrics['recentRecords'] as $record)
                            <x-ui.row-link :href="$record['url']">
                                <td class="px-4 py-3 font-medium text-slate-900 sm:px-6">{{ $record['case_name'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $record['occurred_at'] }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ $record['url'] }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">
                                        {{ $record['constituent'] }}
                                    </a>
                                </td>
                            </x-ui.row-link>
                        @endforeach
                    </tbody>
                </x-ui.table>
            @else
                <x-ui.empty-state message="No criminal records on file." />
            @endif
        </div>
    </div>
</x-layouts.app>
