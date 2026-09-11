<x-layouts.guest title="Register">
    <h1 class="text-lg font-semibold text-slate-900">Create an account</h1>
    <p class="mt-1 text-sm text-slate-600">Register to start profiling constituents.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf

        <x-form.errors />

        <div class="grid gap-5 sm:grid-cols-3">
            <x-form.input name="first_name" label="First name" :required="true" autocomplete="given-name" autofocus />

            <x-form.input name="middle_name" label="Middle name" autocomplete="additional-name" hint="Optional." />

            <x-form.input name="last_name" label="Last name" :required="true" autocomplete="family-name" />
        </div>

        <x-form.input name="email" label="Email" type="email" :required="true" autocomplete="username" />

        <x-form.input name="password" label="Password" type="password" :required="true" autocomplete="new-password" />

        <x-form.input
            name="password_confirmation"
            label="Confirm password"
            type="password"
            :required="true"
            autocomplete="new-password"
        />

        <x-ui.button type="submit" class="w-full">Register</x-ui.button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Already registered?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Log in</a>
    </p>
</x-layouts.guest>
