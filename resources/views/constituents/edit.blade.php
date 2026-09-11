<x-layouts.app :title="'Edit '.$constituent->full_name">
    <x-slot:header>
        <x-ui.page-header title="Edit Constituent" :subtitle="$constituent->full_name">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('constituents.show', $constituent)">View profile</x-ui.button>
                <x-ui.button variant="secondary" :href="route('constituents.index')">Back to list</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <form method="POST" action="{{ route('constituents.update', $constituent) }}" class="max-w-3xl">
        @csrf
        @method('PUT')

        <x-ui.card title="Constituent details">
            @include('constituents._form', ['constituent' => $constituent, 'barangayCaptains' => $barangayCaptains])
        </x-ui.card>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit">Update Constituent</x-ui.button>
            <x-ui.button variant="ghost" :href="route('constituents.show', $constituent)">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts.app>
