@php
    $constituent = $criminalRecord->constituent;
@endphp

<x-layouts.app title="Edit Criminal Record">
    <x-slot:header>
        <x-ui.page-header title="Edit Criminal Record" :subtitle="$constituent->full_name.' · '.$criminalRecord->case_name">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('constituents.show', $constituent)">
                    Back to {{ $constituent->full_name }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <nav class="mb-4 text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('constituents.index') }}" class="hover:text-brand-700">Constituents</a>
        <span class="px-1">/</span>
        <a href="{{ route('constituents.show', $constituent) }}" class="hover:text-brand-700">{{ $constituent->full_name }}</a>
        <span class="px-1">/</span>
        <span class="text-slate-700">{{ $criminalRecord->case_name }}</span>
    </nav>

    <form method="POST" action="{{ route('criminal-records.update', $criminalRecord) }}" class="max-w-2xl">
        @csrf
        @method('PUT')

        <x-ui.card title="Case details">
            @include('criminal-records._form', ['criminalRecord' => $criminalRecord])
        </x-ui.card>

        <div class="mt-5 flex items-center gap-3">
            <x-ui.button type="submit">Update Criminal Record</x-ui.button>
            <x-ui.button variant="ghost" :href="route('constituents.show', $constituent)">Cancel</x-ui.button>
        </div>
    </form>
</x-layouts.app>
