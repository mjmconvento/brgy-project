<?php

use App\Models\Constituent;
use App\Models\CriminalRecord;

beforeEach(function () {
    asAdmin();
});

it('attaches a new criminal record to the constituent in the nested route', function () {
    $constituent = Constituent::factory()->create();

    $this->post(route('constituents.criminal-records.store', $constituent), [
        'case_name' => 'Reckless driving',
        'details' => 'Hit a parked tricycle along the main road.',
        'occurred_at' => '2026-02-14T21:30',
    ])->assertRedirect(route('constituents.show', $constituent))
        ->assertSessionHas('active_tab', 'criminal_records');

    $record = CriminalRecord::query()->sole();

    expect($record->constituent_id)->toBe($constituent->id)
        ->and($record->case_name)->toBe('Reckless driving')
        ->and($record->occurred_at->format('Y-m-d H:i'))->toBe('2026-02-14 21:30');
});

it('rejects an incident dated in the future', function () {
    $constituent = Constituent::factory()->create();

    $this->post(route('constituents.criminal-records.store', $constituent), [
        'case_name' => 'Time travel',
        'occurred_at' => now()->addYear()->format('Y-m-d\TH:i'),
    ])->assertSessionHasErrors('occurred_at');

    expect(CriminalRecord::query()->count())->toBe(0);
});

it('rejects a record with no case name', function () {
    $constituent = Constituent::factory()->create();

    $this->post(route('constituents.criminal-records.store', $constituent), [
        'case_name' => '',
        'occurred_at' => '2026-02-14T21:30',
    ])->assertSessionHasErrors('case_name');

    expect(CriminalRecord::query()->count())->toBe(0);
});

it('updates a criminal record through the shallow route', function () {
    $record = CriminalRecord::factory()->create();

    $this->put(route('criminal-records.update', $record), [
        'case_name' => 'Amended charge',
        'details' => null,
        'occurred_at' => '2025-12-01T08:00',
    ])->assertRedirect(route('constituents.show', $record->constituent));

    expect($record->refresh()->case_name)->toBe('Amended charge')
        ->and($record->details)->toBeNull();
});

it('deletes a criminal record', function () {
    $record = CriminalRecord::factory()->create();

    $this->delete(route('criminal-records.destroy', $record))
        ->assertRedirect(route('constituents.show', $record->constituent));

    expect(CriminalRecord::query()->count())->toBe(0);
});
