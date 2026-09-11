@php
    /** @var \App\Models\Tax|null $tax */
    $statusOptions = collect($statuses)
        ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
        ->all();
@endphp

<div class="space-y-5">
    <x-form.errors />

    <x-form.input
        name="amount"
        label="Amount"
        type="number"
        step="0.01"
        min="0"
        :value="$tax?->amount"
        :required="true"
        hint="Philippine peso amount for the period."
    />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.select
            name="payment_month"
            label="Payment month"
            :options="$months"
            :selected="$tax?->payment_month ?? now()->month"
            :required="true"
        />

        <x-form.input
            name="payment_year"
            label="Payment year"
            type="number"
            min="1900"
            :max="now()->year + 1"
            :value="$tax?->payment_year ?? now()->year"
            :required="true"
        />
    </div>

    <x-form.select
        name="status"
        label="Status"
        :options="$statusOptions"
        :selected="$tax?->status?->value ?? 'unpaid'"
        :required="true"
    />
</div>
