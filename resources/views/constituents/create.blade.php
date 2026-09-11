<x-layouts.app title="Add Constituent">
    <x-slot:header>
        <x-ui.page-header title="Add Constituent" subtitle="Register a new resident.">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('constituents.index')">Back to list</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <form method="POST" action="{{ route('constituents.store') }}" class="max-w-3xl">
        @csrf

        <x-ui.card title="Constituent details">
            @include('constituents._form', ['constituent' => null, 'barangayCaptains' => $barangayCaptains])
        </x-ui.card>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit">Save Constituent</x-ui.button>
            <x-ui.button variant="ghost" :href="route('constituents.index')">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts.app>
