<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('redirects guests from the application to the login screen', function () {
    $this->get(route('constituents.index'))->assertRedirect(route('login'));
});

it('sends an authenticated user away from the login screen', function () {
    asAdmin();

    $this->get(route('login'))->assertRedirect(route('dashboard'));
});

it('signs a user in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'clerk@brgy.local',
        'password' => Hash::make('correct-horse'),
    ]);

    $this->post(route('login'), [
        'email' => 'clerk@brgy.local',
        'password' => 'correct-horse',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid password without signing anyone in', function () {
    User::factory()->create([
        'email' => 'clerk@brgy.local',
        'password' => Hash::make('correct-horse'),
    ]);

    $this->from(route('login'))->post(route('login'), [
        'email' => 'clerk@brgy.local',
        'password' => 'wrong',
    ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('locks a user out after five failed attempts', function () {
    User::factory()->create(['email' => 'clerk@brgy.local']);

    foreach (range(1, 5) as $ignored) {
        $this->post(route('login'), [
            'email' => 'clerk@brgy.local',
            'password' => 'wrong',
        ]);
    }

    $this->post(route('login'), [
        'email' => 'clerk@brgy.local',
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Too many login attempts');
});

it('registers a new administrator and signs them in', function () {
    $this->post(route('register'), [
        'first_name' => 'Nova',
        'middle_name' => 'Santos',
        'last_name' => 'Clerk',
        'email' => 'nova@brgy.local',
        'password' => 'password-please',
        'password_confirmation' => 'password-please',
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'nova@brgy.local')->sole();

    expect(Hash::check('password-please', $user->password))->toBeTrue()
        ->and($user->full_name)->toBe('Nova Santos Clerk');

    $this->assertAuthenticatedAs($user);
});

it('rejects a registration missing the required name parts', function () {
    $this->post(route('register'), [
        'first_name' => '',
        'last_name' => '',
        'email' => 'nova@brgy.local',
        'password' => 'password-please',
        'password_confirmation' => 'password-please',
    ])->assertSessionHasErrors(['first_name', 'last_name']);

    expect(User::query()->where('email', 'nova@brgy.local')->exists())->toBeFalse();
});

it('logs a user out and clears the session', function () {
    asAdmin();

    $this->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});
