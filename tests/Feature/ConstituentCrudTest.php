<?php

use App\Models\BarangayCaptain;
use App\Models\Constituent;
use App\Models\CriminalRecord;
use App\Models\Tax;

beforeEach(function () {
    asAdmin();
});

/**
 * A complete, valid constituent payload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function constituentPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Amihan',
        'middle_name' => 'Santos',
        'last_name' => 'Reyes',
        'house_number' => '74A',
        'street' => 'Mariveles Street',
        'barangay' => 'San Antonio',
        'city' => 'Quezon City',
        'country' => 'Philippines',
        ...$overrides,
    ];
}

it('lists constituents and filters them by the search term', function () {
    Constituent::factory()->create(['first_name' => 'Amihan', 'last_name' => 'Reyes']);
    Constituent::factory()->create(['first_name' => 'Bayani', 'last_name' => 'Cruz']);

    $this->get(route('constituents.index'))
        ->assertOk()
        ->assertSee('Amihan')
        ->assertSee('Bayani');

    $this->get(route('constituents.index', ['search' => 'Amihan']))
        ->assertOk()
        ->assertSee('Amihan')
        ->assertDontSee('Bayani');
});

it('paginates the listing at fifteen rows', function () {
    Constituent::factory()->count(18)->create();

    $first = $this->get(route('constituents.index'));
    $first->assertOk();

    expect($first->viewData('constituents')->count())->toBe(15)
        ->and($first->viewData('constituents')->total())->toBe(18)
        ->and($first->viewData('constituents')->hasMorePages())->toBeTrue();

    $second = $this->get(route('constituents.index', ['page' => 2]));
    $second->assertOk();

    expect($second->viewData('constituents')->count())->toBe(3);
});

it('finds a constituent by any part of their address', function () {
    // Both addresses are pinned: the factory draws a random street/barangay/city,
    // which could otherwise contain one of the search terms and make the
    // negative assertion below flap. `withoutCaptain` keeps a randomly named
    // captain out of the rendered row for the same reason.
    Constituent::factory()->withoutCaptain()->create([
        'first_name' => 'Amihan',
        'house_number' => '12',
        'street' => 'Mabini Street',
        'barangay' => 'Bagong Silang',
        'city' => 'Caloocan',
    ]);

    Constituent::factory()->withoutCaptain()->create([
        'first_name' => 'Bayani',
        'house_number' => '34',
        'street' => 'Yakal Street',
        'barangay' => 'Poblacion',
        'city' => 'Davao City',
    ]);

    foreach (['Mabini', 'Bagong Silang', 'Caloocan', '12'] as $term) {
        $this->get(route('constituents.index', ['search' => $term]))
            ->assertOk()
            ->assertSee('Amihan')
            ->assertDontSee('Bayani');
    }
});

it('matches the search term regardless of case', function () {
    // Guards the one search behaviour that is engine-dependent: on PostgreSQL,
    // which this application runs on, plain `LIKE` is case-sensitive. Every
    // other search assertion in this file happens to use the stored casing, so
    // without this test the scope could regress to `like` and silently break
    // every lowercase lookup while the suite stayed green.
    Constituent::factory()->withoutCaptain()->create([
        'first_name' => 'Amihan',
        'last_name' => 'Dela Cruz',
        'street' => 'Mabini Street',
        'barangay' => 'San Antonio',
        'city' => 'Quezon City',
    ]);

    foreach (['dela cruz', 'DELA CRUZ', 'dElA cRuZ', 'amihan', 'mabini', 'QUEZON'] as $term) {
        $this->get(route('constituents.index', ['search' => $term]))
            ->assertOk()
            ->assertSee('Amihan');
    }
});

it('stores a new constituent', function () {
    $captain = BarangayCaptain::factory()->create();

    $response = $this->post(route('constituents.store'), constituentPayload([
        'barangay_captain_id' => $captain->id,
    ]));

    $constituent = Constituent::query()->sole();

    $response->assertRedirect(route('constituents.show', $constituent))
        ->assertSessionHas('status');

    expect($constituent->full_name)->toBe('Amihan Santos Reyes')
        ->and($constituent->barangay_captain_id)->toBe($captain->id)
        ->and($constituent->street_address)->toBe('74A Mariveles Street')
        ->and($constituent->full_address)->toBe('74A Mariveles Street, Brgy. San Antonio, Quezon City, Philippines');
});

it('accepts a constituent with no captain, middle name, or house number', function () {
    $this->post(route('constituents.store'), constituentPayload([
        'middle_name' => '',
        'house_number' => '',
        'barangay_captain_id' => '',
    ]))->assertSessionHasNoErrors();

    $constituent = Constituent::query()->sole();

    expect($constituent->middle_name)->toBeNull()
        ->and($constituent->house_number)->toBeNull()
        ->and($constituent->barangay_captain_id)->toBeNull()
        ->and($constituent->full_name)->toBe('Amihan Reyes')
        ->and($constituent->street_address)->toBe('Mariveles Street')
        ->and($constituent->full_address)->toBe('Mariveles Street, Brgy. San Antonio, Quezon City, Philippines');
});

it('rejects a constituent that is missing required fields', function () {
    $this->post(route('constituents.store'), [
        'first_name' => '',
        'last_name' => '',
        'street' => '',
        'barangay' => '',
        'city' => '',
        'country' => '',
    ])->assertSessionHasErrors([
        'first_name',
        'last_name',
        'street',
        'barangay',
        'city',
        'country',
    ]);

    expect(Constituent::query()->count())->toBe(0);
});

it('rejects a captain that does not exist', function () {
    $this->post(route('constituents.store'), constituentPayload([
        'barangay_captain_id' => 9999,
    ]))->assertSessionHasErrors('barangay_captain_id');

    expect(Constituent::query()->count())->toBe(0);
});

it('updates an existing constituent', function () {
    $constituent = Constituent::factory()->create(['last_name' => 'Reyes']);

    $this->put(route('constituents.update', $constituent), constituentPayload([
        'last_name' => 'Reyes-Cruz',
        'street' => 'Rizal Avenue',
        'city' => 'Pasig',
        'barangay_captain_id' => $constituent->barangay_captain_id,
    ]))->assertRedirect(route('constituents.show', $constituent));

    $constituent->refresh();

    expect($constituent->last_name)->toBe('Reyes-Cruz')
        ->and($constituent->street)->toBe('Rizal Avenue')
        ->and($constituent->city)->toBe('Pasig');
});

it('deletes a constituent and cascades their tax and criminal records', function () {
    $constituent = Constituent::factory()->create();
    Tax::factory()->count(3)->for($constituent)->create();
    CriminalRecord::factory()->count(2)->for($constituent)->create();

    $this->delete(route('constituents.destroy', $constituent))
        ->assertRedirect(route('constituents.index'));

    expect(Constituent::query()->count())->toBe(0)
        ->and(Tax::query()->count())->toBe(0)
        ->and(CriminalRecord::query()->count())->toBe(0);
});

it('shows a constituent with their records', function () {
    $constituent = Constituent::factory()->create();
    $tax = Tax::factory()->for($constituent)->unpaid()->forPeriod(2026, 3)->create(['amount' => 1234.50]);
    $record = CriminalRecord::factory()->for($constituent)->create(['case_name' => 'Reckless driving']);

    $this->get(route('constituents.show', $constituent))
        ->assertOk()
        ->assertSee($constituent->full_name)
        ->assertSee($constituent->full_address)
        ->assertSee($tax->period_label)
        ->assertSee('1,234.50')
        ->assertSee($record->case_name);
});
