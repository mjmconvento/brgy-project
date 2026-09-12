<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Show the signed-in user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the signed-in user's own profile.
     *
     * A verified flag belongs to the address it was earned for. Changing the
     * address clears it and sends a link to the new one; the `verified` route
     * middleware then closes the application until that link is clicked.
     */
    public function update(ProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->fill($request->validated());

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', $emailChanged
                ? sprintf('Profile updated. A verification link has been sent to %s.', $user->email)
                : 'Profile updated.');
    }
}
