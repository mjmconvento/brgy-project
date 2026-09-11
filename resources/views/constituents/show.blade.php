<x-layouts.app :title="$constituent->full_name">
    <x-slot:header>
        <x-ui.page-header :title="$constituent->full_name" subtitle="Constituent profile">
            <x-slot:actions>
                <x-ui.button variant="secondary" :href="route('constituents.index')">Back to list</x-ui.button>
                <x-ui.button variant="secondary" :href="route('constituents.edit', $constituent)">Edit</x-ui.button>
                <x-ui.delete-button
                    :action="route('constituents.destroy', $constituent)"
                    :confirm="'Delete '.$constituent->full_name.'? This also removes their tax and criminal records.'"
                />
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot:header>

    <div class="space-y-6" x-data="{ tab: @js(session('active_tab', 'details')) }">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Outstanding taxes</p>
                @if ($constituent->outstanding_tax_total > 0)
                    <p class="mt-1 text-2xl font-semibold text-red-700">
                        &#8369;{{ number_format($constituent->outstanding_tax_total, 2) }}
                    </p>
                    <p class="mt-1 text-xs text-red-700">Unpaid balance on record.</p>
                @else
                    <p class="mt-1 text-2xl font-semibold text-green-700">&#8369;0.00</p>
                    <p class="mt-1 text-xs text-slate-500">No unpaid taxes.</p>
                @endif
            </div>

            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tax records</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $constituent->taxes->count() }}</p>
            </div>

            <div class="card p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Criminal records</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $constituent->criminalRecords->count() }}</p>
            </div>
        </div>

        <div class="card">
            <div class="border-b border-slate-200 px-2 sm:px-4">
                <div class="-mb-px flex gap-1 overflow-x-auto" role="tablist" aria-label="Constituent sections">
                    @foreach (['details' => 'Details', 'taxes' => 'Tax Records', 'criminal_records' => 'Criminal Records'] as $key => $tabLabel)
                        <button
                            type="button"
                            role="tab"
                            id="tab-{{ $key }}"
                            aria-controls="panel-{{ $key }}"
                            @click="tab = @js($key)"
                            :class="tab === @js($key)
                                ? 'border-brand-700 text-brand-800'
                                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                            :aria-selected="tab === @js($key)"
                            class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold"
                        >
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Details --}}
            <div x-show="tab === 'details'" id="panel-details" role="tabpanel" aria-labelledby="tab-details" class="px-4 py-5 sm:px-6">
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Full name</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $constituent->full_name }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Voted barangay captain</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            @if ($constituent->barangayCaptain)
                                <a
                                    href="{{ route('barangay-captains.show', $constituent->barangayCaptain) }}"
                                    class="font-semibold text-brand-700 hover:text-brand-900"
                                >
                                    {{ $constituent->barangayCaptain->full_name }}
                                </a>
                            @else
                                &mdash;
                            @endif
                        </dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Address</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ $constituent->full_address }}
                            <a
                                href="https://www.google.com/maps/search/?api=1&amp;query={{ urlencode($constituent->full_address) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="ml-2 text-sm font-semibold text-brand-700 hover:text-brand-900"
                            >
                                View on map
                            </a>

                            <dl class="mt-3 grid gap-3 rounded-lg bg-slate-50 px-4 py-3 sm:grid-cols-3">
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">House number</dt>
                                    <dd class="text-sm text-slate-900">{{ $constituent->house_number ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Street</dt>
                                    <dd class="text-sm text-slate-900">{{ $constituent->street }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Barangay</dt>
                                    <dd class="text-sm text-slate-900">{{ $constituent->barangay }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">City</dt>
                                    <dd class="text-sm text-slate-900">{{ $constituent->city }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-slate-500">Country</dt>
                                    <dd class="text-sm text-slate-900">{{ $constituent->country }}</dd>
                                </div>
                            </dl>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Registered</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $constituent->created_at->format('M j, Y') }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</dt>
                        <dd class="mt-1 flex flex-wrap gap-2">
                            @if ($constituent->has_unpaid_taxes)
                                <x-ui.badge color="red">Unpaid taxes</x-ui.badge>
                            @else
                                <x-ui.badge color="green">Taxes clear</x-ui.badge>
                            @endif

                            @if ($constituent->has_criminal_record)
                                <x-ui.badge color="red">Has criminal record</x-ui.badge>
                            @else
                                <x-ui.badge color="green">No criminal record</x-ui.badge>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Tax records --}}
            <div x-show="tab === 'taxes'" id="panel-taxes" role="tabpanel" aria-labelledby="tab-taxes" style="display: none">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <h2 class="text-sm font-semibold text-slate-900">Tax Records</h2>
                    <x-ui.button :href="route('constituents.taxes.create', $constituent)">Add Tax Record</x-ui.button>
                </div>

                @if ($constituent->taxes->isEmpty())
                    <x-ui.empty-state message="No tax records for this constituent yet.">
                        <x-slot:action>
                            <x-ui.button :href="route('constituents.taxes.create', $constituent)">Add Tax Record</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @else
                    <x-ui.table class="border-t border-slate-200">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3 sm:px-6">Period</th>
                                <th scope="col" class="px-4 py-3">Amount</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3 text-right sm:px-6">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($constituent->taxes as $tax)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-900 sm:px-6">{{ $tax->period_label }}</td>
                                    <td class="px-4 py-3 text-slate-700">&#8369;{{ number_format($tax->amount, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="badge {{ $tax->status->badgeClasses() }}">{{ $tax->status->label() }}</span>
                                    </td>
                                    <td class="px-4 py-3 sm:px-6">
                                        <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                            <a href="{{ route('taxes.edit', $tax) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Edit</a>
                                            <x-ui.delete-button
                                                :action="route('taxes.destroy', $tax)"
                                                :confirm="'Delete the tax record for '.$tax->period_label.'?'"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </div>

            {{-- Criminal records --}}
            <div x-show="tab === 'criminal_records'" id="panel-criminal_records" role="tabpanel" aria-labelledby="tab-criminal_records" style="display: none">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <h2 class="text-sm font-semibold text-slate-900">Criminal Records</h2>
                    <x-ui.button :href="route('constituents.criminal-records.create', $constituent)">Add Criminal Record</x-ui.button>
                </div>

                @if ($constituent->criminalRecords->isEmpty())
                    <x-ui.empty-state message="No criminal records for this constituent.">
                        <x-slot:action>
                            <x-ui.button :href="route('constituents.criminal-records.create', $constituent)">Add Criminal Record</x-ui.button>
                        </x-slot:action>
                    </x-ui.empty-state>
                @else
                    <x-ui.table class="border-t border-slate-200">
                        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-4 py-3 sm:px-6">Case</th>
                                <th scope="col" class="px-4 py-3">Occurred</th>
                                <th scope="col" class="px-4 py-3">Details</th>
                                <th scope="col" class="px-4 py-3 text-right sm:px-6">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($constituent->criminalRecords as $record)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 font-medium text-slate-900 sm:px-6">{{ $record->case_name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-700">{{ $record->occurred_at->format('M j, Y g:i A') }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ str($record->details ?? '')->limit(60) }}</td>
                                    <td class="px-4 py-3 sm:px-6">
                                        <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                            <a href="{{ route('criminal-records.edit', $record) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Edit</a>
                                            <x-ui.delete-button
                                                :action="route('criminal-records.destroy', $record)"
                                                :confirm="'Delete the record for case '.$record->case_name.'?'"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
