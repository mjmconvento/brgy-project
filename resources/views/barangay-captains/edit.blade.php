<x-layouts.app :title="'Edit '.$barangayCaptain->full_name">
    <x-slot:header>
        <x-ui.page-header title="Edit Barangay Captain" :subtitle="$barangayCaptain->full_name">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('barangay-captains.show', $barangayCaptain)">View profile</x-ui.button>
                <x-ui.button variant="secondary" :href="route('barangay-captains.index')">Back to list</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <form method="POST" action="{{ route('barangay-captains.update', $barangayCaptain) }}" class="max-w-3xl">
        @csrf
        @method('PUT')

        <x-ui.card title="Barangay captain details">
            @include('barangay-captains._form', ['barangayCaptain' => $barangayCaptain])
        </x-ui.card>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit">Update Barangay Captain</x-ui.button>
            <x-ui.button variant="ghost" :href="route('barangay-captains.show', $barangayCaptain)">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts.app>
