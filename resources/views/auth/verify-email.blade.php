<x-layouts.guest title="Verify your email">
    <h1 class="text-lg font-semibold text-slate-900">Check your email</h1>
    <p class="mt-1 text-sm text-slate-600">
        We sent a verification link to
        <span class="font-semibold text-slate-900">{{ $email }}</span>.
        Open it to finish setting up your account. The link is valid for {{ $expiresInMinutes }} minutes.
    </p>

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf

        <x-ui.button type="submit" class="w-full">Resend verification email</x-ui.button>
    </form>

    <p class="mt-4 text-center text-sm text-slate-600">
        Wrong address?
        <a href="{{ route('profile.edit') }}" class="font-semibold text-brand-700 hover:text-brand-900">Update it in your profile</a>
    </p>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 border-t border-slate-200 pt-4 text-center">
        @csrf

        <button type="submit" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Log out</button>
    </form>
</x-layouts.guest>
