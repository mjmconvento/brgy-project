<x-layouts.app :title="$barangayCaptain->full_name">
    <x-slot:header>
        <x-ui.page-header :title="$barangayCaptain->full_name" subtitle="Barangay captain profile">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('barangay-captains.index')">Back to list</x-ui.button>
                <x-ui.button variant="secondary" :href="route('barangay-captains.edit', $barangayCaptain)">Edit</x-ui.button>
                <x-ui.delete-button
                    :action="route('barangay-captains.destroy', $barangayCaptain)"
                    :confirm="'Delete '.$barangayCaptain->full_name.'?'"
                />
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <div class="space-y-6">
        <x-ui.card title="Details">
            <dl class="grid gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Full name</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $barangayCaptain->full_name }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registered</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $barangayCaptain->created_at->format('M j, Y') }}</dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Address</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        {{ $barangayCaptain->full_address }}
                        <a
                            href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($barangayCaptain->full_address) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="ml-2 text-sm font-semibold text-brand-700 hover:text-brand-900"
                        >
                            View on map
                        </a>
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        <div class="card">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-6">
                <h2 class="text-sm font-semibold text-slate-900">Constituents</h2>
                <x-ui.badge color="blue">{{ number_format($constituents->total()) }} total</x-ui.badge>
            </div>

            @if ($constituents->isEmpty())
                <x-ui.empty-state message="No constituents have voted for this barangay captain." />
            @else
                <x-ui.table>
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 sm:px-6">Name</th>
                            <th scope="col" class="px-4 py-3">Address</th>
                            <th scope="col" class="px-4 py-3 text-right sm:px-6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($constituents as $constituent)
                            <x-ui.row-link :href="route('constituents.show', $constituent)">
                                <td class="px-4 py-3 font-medium text-slate-900 sm:px-6">{{ $constituent->full_name }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="text-slate-700">{{ $constituent->street_address }}</div>
                                    <div class="text-xs text-slate-500">Brgy. {{ $constituent->barangay }}, {{ $constituent->city }}</div>
                                </td>
                                <td class="px-4 py-3 text-right sm:px-6">
                                    <a href="{{ route('constituents.show', $constituent) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">
                                        View profile
                                    </a>
                                </td>
                            </x-ui.row-link>
                        @endforeach
                    </tbody>
                </x-ui.table>

                <div class="border-t border-slate-200 px-4 py-3 sm:px-6">
                    {{ $constituents->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
