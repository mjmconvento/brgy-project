<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * The URL Laravel's VerifyEmail notification puts in the email.
 */
function verificationLinkFor(User $user, ?string $hash = null): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => $hash ?? sha1($user->email),
    ]);
}

it('keeps an unverified user out of the application', function () {
    asAdmin(User::factory()->unverified()->create());

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->get(route('constituents.index'))->assertRedirect(route('verification.notice'));
});

it('leaves the profile reachable so a mistyped address can be fixed', function () {
    asAdmin(User::factory()->unverified()->create());

    $this->get(route('profile.edit'))->assertOk();
});

it('shows the notice with the address the link went to', function () {
    asAdmin(User::factory()->unverified()->create(['email' => 'nova@brgy.local']));

    $this->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('nova@brgy.local');
});

it('sends a verified user from the notice straight to the dashboard', function () {
    asAdmin();

    $this->get(route('verification.notice'))->assertRedirect(route('dashboard'));
});

it('verifies the address from the signed link', function () {
    Event::fake([Verified::class]);

    $user = asAdmin(User::factory()->unverified()->create());

    $this->get(verificationLinkFor($user))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status');

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();

    Event::assertDispatched(Verified::class);
});

it('rejects a link whose hash is not the current address', function () {
    $user = asAdmin(User::factory()->unverified()->create());

    $this->get(verificationLinkFor($user, sha1('someone-else@brgy.local')))->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects a link without a valid signature', function () {
    $user = asAdmin(User::factory()->unverified()->create());

    $this->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))
        ->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('brings a signed-out visitor back to the link after logging in', function () {
    $user = User::factory()->unverified()->create(['email' => 'nova@brgy.local']);
    $link = verificationLinkFor($user);

    $this->get($link)->assertRedirect(route('login'));

    $this->post(route('login'), [
        'email' => 'nova@brgy.local',
        'password' => 'password',
    ])->assertRedirect($link);

    $this->get($link)->assertRedirect(route('dashboard'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('resends the link to an unverified user', function () {
    Notification::fake();

    $user = asAdmin(User::factory()->unverified()->create());

    $this->from(route('verification.notice'))
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('does not resend to an already verified user', function () {
    Notification::fake();

    asAdmin();

    $this->post(route('verification.send'))->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();
});
