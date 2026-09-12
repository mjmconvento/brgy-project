<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Email verification for a session-authenticated, server-rendered app.
 *
 * The three routes live behind `auth` but outside `verified`: an unverified
 * account has to be able to reach them by definition. The link in the email is
 * a temporary signed URL, so opening it while signed out first bounces through
 * the login form and `redirect()->intended()` brings the person back here.
 */
class EmailVerificationController extends Controller
{
    /**
     * Show the "check your inbox" page — the landing page of a fresh account.
     */
    public function notice(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Re-visiting the page after clicking the link is not an error.
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.verify-email', [
            'email' => $user->email,
            'expiresInMinutes' => Config::integer('auth.verification.expire', 60),
        ]);
    }

    /**
     * Mark the address verified from the signed link in the email.
     *
     * `EmailVerificationRequest` authorises the request: the route id must be the
     * signed-in user and the hash must match their current address, so a link
     * stops working the moment the address changes. The `signed` middleware on
     * the route has already rejected tampered or expired URLs.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Your email address has been verified.');
    }

    /**
     * Send the link again, for the signed-in account.
     *
     * Without this an expired or lost link means registering a second account,
     * which is a silly reason to create data.
     */
    public function send(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard'));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', sprintf('A new verification link has been sent to %s.', $user->email));
    }
}
