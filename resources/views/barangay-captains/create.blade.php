<x-layouts.app title="Add Barangay Captain">
    <x-slot:header>
        <x-ui.page-header title="Add Barangay Captain">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('barangay-captains.index')">Back to list</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <form method="POST" action="{{ route('barangay-captains.store') }}" class="max-w-3xl">
        @csrf

        <x-ui.card title="Barangay captain details">
            @include('barangay-captains._form', ['barangayCaptain' => null])
        </x-ui.card>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit">Save Barangay Captain</x-ui.button>
            <x-ui.button variant="ghost" :href="route('barangay-captains.index')">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts.app>
