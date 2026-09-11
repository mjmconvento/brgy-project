@php
    /** @var \App\Models\BarangayCaptain|null $barangayCaptain */
@endphp

<div class="space-y-5">
    <x-form.errors />

    <div class="grid gap-5 sm:grid-cols-3">
        <x-form.input name="first_name" label="First name" :value="$barangayCaptain?->first_name" :required="true" />
        <x-form.input name="middle_name" label="Middle name" :value="$barangayCaptain?->middle_name" />
        <x-form.input name="last_name" label="Last name" :value="$barangayCaptain?->last_name" :required="true" />
    </div>

    <fieldset class="space-y-4 border-t border-slate-200 pt-5">
        <legend class="sr-only">Address</legend>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Address</h3>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input
                name="house_number"
                label="House number"
                :value="$barangayCaptain?->house_number"
                hint="Optional — leave blank if none."
            />
            <x-form.input name="street" label="Street" :value="$barangayCaptain?->street" :required="true" />
            <x-form.input name="barangay" label="Barangay" :value="$barangayCaptain?->barangay" :required="true" />
            <x-form.input name="city" label="City or municipality" :value="$barangayCaptain?->city" :required="true" />
            <x-form.input
                name="country"
                label="Country"
                :value="old('country', $barangayCaptain?->country ?? 'Philippines')"
                :required="true"
            />
        </div>
    </fieldset>
</div>
