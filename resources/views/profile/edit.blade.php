<x-layouts.app title="Profile">
    <x-slot:header>
        <x-ui.page-header title="Profile" subtitle="Update your account details." />
    </x-slot:header>

    <form method="POST" action="{{ route('profile.update') }}" class="max-w-2xl">
        @csrf
        @method('PUT')

        <x-ui.card title="Account">
            <div class="space-y-5">
                <x-form.errors />

                <div class="grid gap-5 sm:grid-cols-3">
                    <x-form.input name="first_name" label="First name" :value="$user->first_name" :required="true" autocomplete="given-name" />

                    <x-form.input name="middle_name" label="Middle name" :value="$user->middle_name" autocomplete="additional-name" hint="Optional." />

                    <x-form.input name="last_name" label="Last name" :value="$user->last_name" :required="true" autocomplete="family-name" />
                </div>

                <x-form.input name="email" label="Email" type="email" :value="$user->email" :required="true" autocomplete="email" />
            </div>
        </x-ui.card>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit">Save changes</x-ui.button>
            <x-ui.button variant="ghost" :href="route('constituents.index')">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts.app>
