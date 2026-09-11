<?php

use App\Models\User;

it('updates the signed-in user own profile', function () {
    $user = asAdmin(User::factory()->create([
        'first_name' => 'Old',
        'middle_name' => 'Middle',
        'last_name' => 'Name',
        'email' => 'old@brgy.local',
    ]));

    $this->put(route('profile.update'), [
        'first_name' => 'Nova',
        'middle_name' => 'Santos',
        'last_name' => 'Clerk',
        'email' => 'new@brgy.local',
    ])->assertRedirect(route('profile.edit'))->assertSessionHas('status');

    $user->refresh();

    expect($user->full_name)->toBe('Nova Santos Clerk')
        ->and($user->email)->toBe('new@brgy.local');
});

it('accepts a user with no middle name', function () {
    $user = asAdmin();

    $this->put(route('profile.update'), [
        'first_name' => 'Nova',
        'middle_name' => '',
        'last_name' => 'Clerk',
        'email' => 'nova@brgy.local',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->middle_name)->toBeNull()
        ->and($user->full_name)->toBe('Nova Clerk');
});

it('refuses an email already taken by another user', function () {
    User::factory()->create(['email' => 'taken@brgy.local']);
    $user = asAdmin();

    $this->put(route('profile.update'), [
        'first_name' => 'Who',
        'last_name' => 'Ever',
        'email' => 'taken@brgy.local',
    ])->assertSessionHasErrors('email');

    expect($user->refresh()->email)->not->toBe('taken@brgy.local');
});

it('lets a user keep their own email address', function () {
    $user = asAdmin(User::factory()->create(['email' => 'mine@brgy.local']));

    $this->put(route('profile.update'), [
        'first_name' => 'Renamed',
        'last_name' => 'Person',
        'email' => 'mine@brgy.local',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->first_name)->toBe('Renamed');
});
