<?php

use App\Enums\TaxStatus;
use App\Models\Constituent;
use App\Models\Tax;

beforeEach(function () {
    asAdmin();
});

it('attaches a new tax record to the constituent in the nested route', function () {
    $constituent = Constituent::factory()->create();
    $other = Constituent::factory()->create();

    $this->post(route('constituents.taxes.store', $constituent), [
        'amount' => '1500.75',
        'payment_month' => 4,
        'payment_year' => 2026,
        'status' => TaxStatus::Unpaid->value,
    ])->assertRedirect(route('constituents.show', $constituent))
        ->assertSessionHas('active_tab', 'taxes');

    $tax = Tax::query()->sole();

    expect($tax->constituent_id)->toBe($constituent->id)
        ->and($tax->constituent_id)->not->toBe($other->id)
        ->and($tax->amount)->toBe('1500.75')
        ->and($tax->status)->toBe(TaxStatus::Unpaid)
        ->and($tax->period_label)->toBe('April 2026');
});

it('refuses a second tax record for the same constituent and period', function () {
    $constituent = Constituent::factory()->create();
    Tax::factory()->for($constituent)->forPeriod(2026, 4)->create();

    $this->post(route('constituents.taxes.store', $constituent), [
        'amount' => '10',
        'payment_month' => 4,
        'payment_year' => 2026,
        'status' => TaxStatus::Paid->value,
    ])->assertSessionHasErrors('payment_month');

    expect(Tax::query()->count())->toBe(1);
});

it('allows the same period for a different constituent', function () {
    $first = Constituent::factory()->create();
    $second = Constituent::factory()->create();
    Tax::factory()->for($first)->forPeriod(2026, 4)->create();

    $this->post(route('constituents.taxes.store', $second), [
        'amount' => '10',
        'payment_month' => 4,
        'payment_year' => 2026,
        'status' => TaxStatus::Paid->value,
    ])->assertSessionHasNoErrors();

    expect(Tax::query()->count())->toBe(2);
});

it('lets a tax record keep its own period while being edited', function () {
    $tax = Tax::factory()->forPeriod(2026, 4)->unpaid()->create();

    $this->put(route('taxes.update', $tax), [
        'amount' => '99.99',
        'payment_month' => 4,
        'payment_year' => 2026,
        'status' => TaxStatus::Paid->value,
    ])->assertSessionHasNoErrors();

    expect($tax->refresh()->status)->toBe(TaxStatus::Paid)
        ->and($tax->amount)->toBe('99.99');
});

it('rejects an out-of-range month and an unknown status', function () {
    $constituent = Constituent::factory()->create();

    $this->post(route('constituents.taxes.store', $constituent), [
        'amount' => '10',
        'payment_month' => 13,
        'payment_year' => 2026,
        'status' => 'waived',
    ])->assertSessionHasErrors(['payment_month', 'status']);

    expect(Tax::query()->count())->toBe(0);
});

it('deletes a tax record and returns to the tax tab', function () {
    $tax = Tax::factory()->create();

    $this->delete(route('taxes.destroy', $tax))
        ->assertRedirect(route('constituents.show', $tax->constituent))
        ->assertSessionHas('active_tab', 'taxes');

    expect(Tax::query()->count())->toBe(0);
});
