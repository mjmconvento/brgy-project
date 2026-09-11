<x-layouts.app title="Barangay Captains">
    <x-slot:header>
        <x-ui.page-header title="Barangay Captains" subtitle="Officials constituents may vote for.">
            <x-slot:actions>
                <x-ui.button :href="route('barangay-captains.create')">Add Barangay Captain</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <div class="card">
        <div class="border-b border-slate-200 px-4 py-3 sm:px-6">
            <x-ui.search-form
                :action="route('barangay-captains.index')"
                :value="$search"
                placeholder="Search name or address…"
            />
        </div>

        @if ($barangayCaptains->isEmpty())
            <x-ui.empty-state :message="filled($search) ? 'No barangay captains match your search.' : 'No barangay captains recorded yet.'">
                <x-slot:action>
                    <x-ui.button :href="route('barangay-captains.create')">Add Barangay Captain</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @else
            <x-ui.table>
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-4 py-3 sm:px-6">Name</th>
                        <th scope="col" class="px-4 py-3">Address</th>
                        <th scope="col" class="px-4 py-3">Constituents</th>
                        <th scope="col" class="px-4 py-3 text-right sm:px-6">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($barangayCaptains as $captain)
                        <x-ui.row-link :href="route('barangay-captains.show', $captain)">
                            <td class="px-4 py-3 font-medium text-slate-900 sm:px-6">
                                <a href="{{ route('barangay-captains.show', $captain) }}" class="hover:text-brand-700">
                                    {{ $captain->last_name }}, {{ $captain->first_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <div class="text-slate-700">{{ $captain->street_address }}</div>
                                <div class="text-xs text-slate-500">Brgy. {{ $captain->barangay }}, {{ $captain->city }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge color="blue">{{ $captain->constituents_count }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 sm:px-6">
                                <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                    <a href="{{ route('barangay-captains.show', $captain) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">View</a>
                                    <a href="{{ route('barangay-captains.edit', $captain) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Edit</a>
                                    <x-ui.delete-button
                                        :action="route('barangay-captains.destroy', $captain)"
                                        :confirm="'Delete '.$captain->full_name.'? Linked constituents will keep their records but lose this vote.'"
                                    />
                                </div>
                            </td>
                        </x-ui.row-link>
                    @endforeach
                </tbody>
            </x-ui.table>

            <div class="border-t border-slate-200 px-4 py-3 sm:px-6">
                {{ $barangayCaptains->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
