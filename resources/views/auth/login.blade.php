<x-layouts.guest title="Log in">
    <h1 class="text-lg font-semibold text-slate-900">Log in</h1>
    <p class="mt-1 text-sm text-slate-600">Sign in to manage barangay records.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <x-form.errors />

        <x-form.input name="email" label="Email" type="email" :required="true" autocomplete="username" autofocus />

        <x-form.input name="password" label="Password" type="password" :required="true" autocomplete="current-password" />

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 rounded border-slate-300 text-brand-700 focus:ring-brand-600">
            Remember me
        </label>

        <x-ui.button type="submit" class="w-full">Log in</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Don't have an account?
        <a href="{{ route('register') }}" class="font-semibold text-brand-700 hover:text-brand-900">Register</a>
    </p>
</x-layouts.guest>
