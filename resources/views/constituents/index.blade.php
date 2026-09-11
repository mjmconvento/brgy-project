<x-layouts.app title="Constituents">
    <x-slot:header>
        <x-ui.page-header title="Constituents" subtitle="Registered residents of the barangay.">
            <x-slot:actions>
                <x-ui.button :href="route('constituents.create')">Add Constituent</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <div class="card">
        <div class="border-b border-slate-200 px-4 py-3 sm:px-6">
            <x-ui.search-form :action="route('constituents.index')" :value="$search" placeholder="Search name or address…" />
        </div>

        @if ($constituents->isEmpty())
            <x-ui.empty-state :message="filled($search) ? 'No constituents match your search.' : 'No constituents recorded yet.'">
                <x-slot:action>
                    <x-ui.button :href="route('constituents.create')">Add Constituent</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 sm:px-6">Name</th>
                        <th scope="col" class="px-4 py-3">Address</th>
                        <th scope="col" class="px-4 py-3">Voted Brgy. Captain</th>
                        <th scope="col" class="px-4 py-3">Taxes</th>
                        <th scope="col" class="px-4 py-3">Record</th>
                        <th scope="col" class="px-4 py-3 text-right sm:px-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($constituents as $constituent)
                        <x-ui.row-link :href="route('constituents.show', $constituent)">
                            <td class="px-4 py-3 font-medium text-slate-900 sm:px-6">
                                <a href="{{ route('constituents.show', $constituent) }}" class="hover:text-brand-700">
                                    {{ $constituent->last_name }}, {{ $constituent->first_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div class="text-slate-700">{{ $constituent->street_address }}</div>
                                <div class="text-xs text-slate-500">Brgy. {{ $constituent->barangay }}, {{ $constituent->city }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $constituent->barangayCaptain?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($constituent->has_unpaid_taxes)
                                    <x-ui.badge color="red">Unpaid</x-ui.badge>
                                @else
                                    <x-ui.badge color="green">Clear</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($constituent->has_criminal_record)
                                    <x-ui.badge color="red">Has record</x-ui.badge>
                                @else
                                    <x-ui.badge color="green">Clean</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                    <a href="{{ route('constituents.show', $constituent) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">View</a>
                                    <a href="{{ route('constituents.edit', $constituent) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Edit</a>
                                    <x-ui.delete-button
                                        :action="route('constituents.destroy', $constituent)"
                                        :confirm="'Delete '.$constituent->full_name.'? This also removes their tax and criminal records.'"
                                    />
                                </div>
                            </td>
                        </x-ui.row-link>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="border-t border-slate-200 px-4 py-3 sm:px-6">
                {{ $constituents->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
