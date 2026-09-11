<?php

use App\Models\BarangayCaptain;
use App\Models\Constituent;

beforeEach(function () {
    asAdmin();
});

/**
 * A complete, valid barangay captain payload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function captainPayload(array $overrides = []): array
{
    return [
        'first_name' => 'Ligaya',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'house_number' => 'Blk 4 Lot 12',
        'street' => 'Bonifacio Street',
        'barangay' => 'Poblacion',
        'city' => 'Makati',
        'country' => 'Philippines',
        ...$overrides,
    ];
}

it('stores a new captain candidate', function () {
    $response = $this->post(route('barangay-captains.store'), captainPayload());

    $captain = BarangayCaptain::query()->sole();

    $response->assertRedirect(route('barangay-captains.show', $captain));

    expect($captain->full_name)->toBe('Ligaya Dela Cruz')
        ->and($captain->full_address)->toBe('Blk 4 Lot 12 Bonifacio Street, Brgy. Poblacion, Makati, Philippines');
});

it('rejects a captain that is missing required fields', function () {
    $this->post(route('barangay-captains.store'), [
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

    expect(BarangayCaptain::query()->count())->toBe(0);
});

it('shows a captain with the constituents who voted for them', function () {
    $captain = BarangayCaptain::factory()->create();
    $voter = Constituent::factory()->for_captain($captain)->create();
    $stranger = Constituent::factory()->create();

    $this->get(route('barangay-captains.show', $captain))
        ->assertOk()
        ->assertSee($voter->full_name)
        ->assertDontSee($stranger->full_name);
});

it('paginates the voter roster', function () {
    $captain = BarangayCaptain::factory()->create();
    Constituent::factory()->count(17)->for_captain($captain)->create();

    $first = $this->get(route('barangay-captains.show', $captain));
    $first->assertOk();

    $paginator = $first->viewData('constituents');

    expect($paginator->count())->toBe(15)
        ->and($paginator->total())->toBe(17)
        ->and($paginator->hasMorePages())->toBeTrue();

    $second = $this->get(route('barangay-captains.show', [$captain, 'constituents_page' => 2]));
    $second->assertOk();

    expect($second->viewData('constituents')->count())->toBe(2);
});

it('counts constituents on the listing', function () {
    $captain = BarangayCaptain::factory()->create();
    Constituent::factory()->count(3)->for_captain($captain)->create();

    $listed = BarangayCaptain::query()->withCount('constituents')->findOrFail($captain->id);

    expect($listed->constituents_count)->toBe(3);
});

it('keeps constituents when their captain is deleted', function () {
    $captain = BarangayCaptain::factory()->create();
    $constituent = Constituent::factory()->for_captain($captain)->create();

    $this->delete(route('barangay-captains.destroy', $captain))
        ->assertRedirect(route('barangay-captains.index'));

    expect(BarangayCaptain::query()->count())->toBe(0)
        ->and($constituent->refresh()->exists)->toBeTrue()
        ->and($constituent->barangay_captain_id)->toBeNull();
});
