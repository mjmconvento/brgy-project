<?php

use App\Models\BarangayCaptain;
use App\Models\Constituent;
use App\Models\CriminalRecord;
use App\Models\Tax;
use App\Queries\DashboardMetrics;

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('renders the dashboard for a signed-in user', function () {
    asAdmin();

    Constituent::factory()->count(3)->create();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertViewHas('metrics');
});

it('renders on an empty database', function () {
    asAdmin();

    $response = $this->get(route('dashboard'))->assertOk();

    $totals = $response->viewData('metrics')['totals'];

    expect($totals['constituents'])->toBe(0)
        ->and($totals['outstandingTotal'])->toBe(0.0)
        ->and($totals['collectedTotal'])->toBe(0.0);
});

it('totals constituents, captains, records and peso amounts', function () {
    $captain = BarangayCaptain::factory()->create();
    $owing = Constituent::factory()->for_captain($captain)->create();
    $settled = Constituent::factory()->for_captain($captain)->create();
    Constituent::factory()->withoutCaptain()->create();

    Tax::factory()->for($owing)->unpaid()->forPeriod(2026, 1)->create(['amount' => 500.25]);
    Tax::factory()->for($owing)->unpaid()->forPeriod(2026, 2)->create(['amount' => 100.75]);
    Tax::factory()->for($owing)->paid()->forPeriod(2026, 3)->create(['amount' => 400.00]);
    Tax::factory()->for($settled)->paid()->forPeriod(2026, 1)->create(['amount' => 600.00]);

    CriminalRecord::factory()->count(2)->for($owing)->create();

    $totals = app(DashboardMetrics::class)->toArray()['totals'];

    expect($totals['constituents'])->toBe(3)
        ->and($totals['captains'])->toBe(1)
        ->and($totals['criminalRecords'])->toBe(2)
        ->and($totals['withUnpaidTaxes'])->toBe(1)
        ->and($totals['withCriminalRecord'])->toBe(1)
        ->and($totals['outstandingTotal'])->toBe(601.0)
        ->and($totals['collectedTotal'])->toBe(1000.0);
});

it('splits tax records by status', function () {
    $constituent = Constituent::factory()->create();
    Tax::factory()->for($constituent)->paid()->forPeriod(2026, 1)->create();
    Tax::factory()->for($constituent)->paid()->forPeriod(2026, 2)->create();
    Tax::factory()->for($constituent)->unpaid()->forPeriod(2026, 3)->create();

    $status = collect(app(DashboardMetrics::class)->toArray()['taxStatus'])
        ->pluck('total', 'label');

    expect($status['Paid'])->toBe(2)
        ->and($status['Unpaid'])->toBe(1);
});

it('always emits twelve monthly buckets, oldest first', function () {
    $constituent = Constituent::factory()->create();
    $thisMonth = now()->startOfMonth();

    Tax::factory()->for($constituent)
        ->forPeriod($thisMonth->year, $thisMonth->month)
        ->unpaid()
        ->create(['amount' => 250.00]);

    $monthly = app(DashboardMetrics::class)->toArray()['monthlyTaxes'];

    expect($monthly)->toHaveCount(12)
        ->and($monthly[0]['label'])->toBe(now()->startOfMonth()->subMonths(11)->format('M Y'))
        ->and($monthly[11]['label'])->toBe($thisMonth->format('M Y'))
        ->and($monthly[11]['unpaid'])->toBe(250.0)
        ->and($monthly[11]['paid'])->toBe(0.0)
        ->and($monthly[0]['unpaid'])->toBe(0.0);
});

it('excludes billing periods older than the chart window', function () {
    $constituent = Constituent::factory()->create();
    $old = now()->startOfMonth()->subMonths(20);

    Tax::factory()->for($constituent)
        ->forPeriod($old->year, $old->month)
        ->unpaid()
        ->create(['amount' => 999.00]);

    $monthly = app(DashboardMetrics::class)->toArray()['monthlyTaxes'];

    expect(collect($monthly)->sum('unpaid'))->toBe(0.0);
});

it('ranks outstanding tax by barangay, highest first', function () {
    $heavy = Constituent::factory()->create(['barangay' => 'San Antonio']);
    $light = Constituent::factory()->create(['barangay' => 'Poblacion']);
    $clear = Constituent::factory()->create(['barangay' => 'Malaya']);

    Tax::factory()->for($heavy)->unpaid()->forPeriod(2026, 1)->create(['amount' => 900.00]);
    Tax::factory()->for($light)->unpaid()->forPeriod(2026, 1)->create(['amount' => 100.00]);
    Tax::factory()->for($clear)->paid()->forPeriod(2026, 1)->create(['amount' => 5000.00]);

    $byBarangay = app(DashboardMetrics::class)->toArray()['outstandingByBarangay'];

    expect($byBarangay)->toHaveCount(2)
        ->and($byBarangay[0])->toBe(['label' => 'San Antonio', 'total' => 900.0])
        ->and($byBarangay[1])->toBe(['label' => 'Poblacion', 'total' => 100.0]);
});

it('ranks constituent headcount by city, highest first', function () {
    Constituent::factory()->count(3)->create(['city' => 'Quezon City']);
    Constituent::factory()->create(['city' => 'Cebu City']);

    $byCity = app(DashboardMetrics::class)->toArray()['constituentsByCity'];

    expect($byCity[0])->toBe(['label' => 'Quezon City', 'total' => 3])
        ->and($byCity[1])->toBe(['label' => 'Cebu City', 'total' => 1]);
});

it('ranks captains by voters and omits captains with none', function () {
    $popular = BarangayCaptain::factory()->create(['first_name' => 'Ligaya', 'middle_name' => null, 'last_name' => 'Cruz']);
    $quiet = BarangayCaptain::factory()->create(['first_name' => 'Bayani', 'middle_name' => null, 'last_name' => 'Reyes']);
    BarangayCaptain::factory()->create();

    Constituent::factory()->count(4)->for_captain($popular)->create();
    Constituent::factory()->for_captain($quiet)->create();

    $top = app(DashboardMetrics::class)->toArray()['topCaptains'];

    expect($top)->toHaveCount(2)
        ->and($top[0])->toBe(['label' => 'Ligaya Cruz', 'total' => 4])
        ->and($top[1])->toBe(['label' => 'Bayani Reyes', 'total' => 1]);
});

it('lists the five most recent criminal records newest first', function () {
    $constituent = Constituent::factory()->create();

    foreach (range(1, 7) as $monthsAgo) {
        CriminalRecord::factory()->for($constituent)->create([
            'case_name' => "Case {$monthsAgo}",
            'occurred_at' => now()->subMonths($monthsAgo),
        ]);
    }

    $recent = app(DashboardMetrics::class)->toArray()['recentRecords'];

    expect($recent)->toHaveCount(5)
        ->and($recent[0]['case_name'])->toBe('Case 1')
        ->and($recent[4]['case_name'])->toBe('Case 5')
        ->and($recent[0]['constituent'])->toBe($constituent->full_name)
        ->and($recent[0]['url'])->toBe(route('constituents.show', $constituent));
});

it('builds the whole dashboard in a bounded number of queries', function () {
    BarangayCaptain::factory()->count(5)->create()->each(function (BarangayCaptain $captain) {
        Constituent::factory()->count(4)->for_captain($captain)->create()->each(function (Constituent $constituent) {
            Tax::factory()->count(2)->for($constituent)->create();
            CriminalRecord::factory()->for($constituent)->create();
        });
    });

    DB::enableQueryLog();

    app(DashboardMetrics::class)->toArray();

    // Aggregates only: the count must not grow with the number of records.
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(12);
});
