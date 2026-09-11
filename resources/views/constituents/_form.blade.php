@php
    /** @var \App\Models\Constituent|null $constituent */
    $captainOptions = $barangayCaptains->pluck('full_name', 'id')->all();
@endphp

<div class="space-y-5">
    <x-form.errors />

    <div class="grid gap-5 sm:grid-cols-3">
        <x-form.input name="first_name" label="First name" :value="$constituent?->first_name" :required="true" />
        <x-form.input name="middle_name" label="Middle name" :value="$constituent?->middle_name" />
        <x-form.input name="last_name" label="Last name" :value="$constituent?->last_name" :required="true" />
    </div>

    <fieldset class="space-y-4 border-t border-slate-200 pt-5">
        <legend class="sr-only">Address</legend>
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Address</h3>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input
                name="house_number"
                label="House number"
                :value="$constituent?->house_number"
                hint="Optional — leave blank if none."
            />
            <x-form.input name="street" label="Street" :value="$constituent?->street" :required="true" />
            <x-form.input name="barangay" label="Barangay" :value="$constituent?->barangay" :required="true" />
            <x-form.input name="city" label="City or municipality" :value="$constituent?->city" :required="true" />
            <x-form.input
                name="country"
                label="Country"
                :value="old('country', $constituent?->country ?? 'Philippines')"
                :required="true"
            />
        </div>
    </fieldset>

    <x-form.select
        name="barangay_captain_id"
        label="Voted barangay captain"
        :options="$captainOptions"
        :selected="$constituent?->barangay_captain_id"
        placeholder="— None —"
    />
</div>
