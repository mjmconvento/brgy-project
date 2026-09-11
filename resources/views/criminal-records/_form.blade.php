@php
    /** @var \App\Models\CriminalRecord|null $criminalRecord */
    $occurredAt = $criminalRecord?->occurred_at?->format('Y-m-d\TH:i');
@endphp

<div class="space-y-5">
    <x-form.errors />

    <x-form.input name="case_name" label="Case name" :value="$criminalRecord?->case_name" :required="true" />

    <x-form.input
        name="occurred_at"
        label="Date and time of incident"
        type="datetime-local"
        :value="$occurredAt"
        :required="true"
    />

    <x-form.textarea
        name="details"
        label="Details"
        :value="$criminalRecord?->details"
        :rows="5"
        hint="Optional narrative of the incident."
    />
</div>
