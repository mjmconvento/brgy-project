<?php

use App\Enums\TaxStatus;
use App\Models\Constituent;
use App\Models\CriminalRecord;
use App\Models\Tax;

/*
|--------------------------------------------------------------------------
| Derived profile state
|--------------------------------------------------------------------------
|
| The legacy schema stored `has_record` and `has_unpaid_tax` as booleans that
| controllers had to keep in sync by hand, so any write path that forgot to call
| the sync helper left the listing lying. These flags are now derived, and these
| tests pin that they always agree with the underlying rows.
|
*/

it('reports no unpaid taxes and no record for a fresh constituent', function () {
    $constituent = Constituent::factory()->create();

    expect($constituent->has_unpaid_taxes)->toBeFalse()
        ->and($constituent->has_criminal_record)->toBeFalse()
        ->and($constituent->outstanding_tax_total)->toBe(0.0);
});

it('derives unpaid state and the outstanding total from tax rows', function () {
    $constituent = Constituent::factory()->create();
    Tax::factory()->for($constituent)->paid()->forPeriod(2026, 1)->create(['amount' => 500]);
    Tax::factory()->for($constituent)->unpaid()->forPeriod(2026, 2)->create(['amount' => 250.25]);
    Tax::factory()->for($constituent)->unpaid()->forPeriod(2026, 3)->create(['amount' => 100.75]);

    $constituent->refresh();

    expect($constituent->has_unpaid_taxes)->toBeTrue()
        ->and($constituent->outstanding_tax_total)->toBe(351.0);
});

it('clears the unpaid flag once the last unpaid record is settled', function () {
    $constituent = Constituent::factory()->create();
    $tax = Tax::factory()->for($constituent)->unpaid()->create(['amount' => 90]);

    expect($constituent->fresh()->has_unpaid_taxes)->toBeTrue();

    $tax->delete();

    expect($constituent->fresh()->has_unpaid_taxes)->toBeFalse()
        ->and($constituent->fresh()->outstanding_tax_total)->toBe(0.0);
});

it('derives the same flags whether aggregates are eager loaded or not', function () {
    $constituent = Constituent::factory()->create();
    Tax::factory()->for($constituent)->unpaid()->create(['amount' => 42.50]);
    CriminalRecord::factory()->for($constituent)->create();

    $lazy = Constituent::query()->findOrFail($constituent->id);
    $eager = Constituent::query()->withListingAggregates()->findOrFail($constituent->id);

    expect($eager->has_unpaid_taxes)->toBe($lazy->has_unpaid_taxes)
        ->and($eager->has_criminal_record)->toBe($lazy->has_criminal_record)
        ->and($eager->outstanding_tax_total)->toBe($lazy->outstanding_tax_total)
        ->and($eager->outstanding_tax_total)->toBe(42.5);
});

it('loads the listing without a query per row', function () {
    Constituent::factory()->count(10)->create()->each(function (Constituent $constituent) {
        Tax::factory()->for($constituent)->unpaid()->create();
        CriminalRecord::factory()->for($constituent)->create();
    });

    DB::enableQueryLog();

    $rendered = Constituent::query()
        ->withListingAggregates()
        ->orderedByName()
        ->get()
        ->map(fn (Constituent $constituent): array => [
            'unpaid' => $constituent->has_unpaid_taxes,
            'record' => $constituent->has_criminal_record,
            'owed' => $constituent->outstanding_tax_total,
            'captain' => $constituent->barangayCaptain?->full_name,
        ]);

    // One select for the constituents (aggregates ride along as subqueries) plus
    // one eager load for the captains.
    expect(DB::getQueryLog())->toHaveCount(2)
        ->and($rendered)->toHaveCount(10)
        ->and($rendered->every(fn (array $row): bool => $row['unpaid'] && $row['record']))->toBeTrue();
});

it('derives the flags from an already loaded taxes collection', function () {
    // The profile page loads the full collections rather than aggregates, so the
    // accessors take their in-memory branch and compare `status` as an enum.
    $constituent = Constituent::factory()->create();
    Tax::factory()->for($constituent)->paid()->forPeriod(2026, 5)->create(['amount' => 800]);
    Tax::factory()->for($constituent)->unpaid()->forPeriod(2026, 6)->create(['amount' => 120.40]);
    CriminalRecord::factory()->for($constituent)->create();

    $loaded = Constituent::query()->with(['taxes', 'criminalRecords'])->findOrFail($constituent->id);

    expect($loaded->relationLoaded('taxes'))->toBeTrue()
        ->and($loaded->taxes->first()->status)->toBeInstanceOf(TaxStatus::class)
        ->and($loaded->has_unpaid_taxes)->toBeTrue()
        ->and($loaded->has_criminal_record)->toBeTrue()
        ->and($loaded->outstanding_tax_total)->toBe(120.40);
});

it('reports a settled taxes collection as clear', function () {
    $constituent = Constituent::factory()->create();
    Tax::factory()->for($constituent)->paid()->forPeriod(2026, 5)->create(['amount' => 800]);

    $loaded = Constituent::query()->with('taxes')->findOrFail($constituent->id);

    expect($loaded->has_unpaid_taxes)->toBeFalse()
        ->and($loaded->outstanding_tax_total)->toBe(0.0);
});
