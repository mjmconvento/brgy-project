@props(['type' => 'info'])

@php
    $styles = match ($type) {
        'success' => 'bg-green-50 text-green-900 border-green-200',
        'error' => 'bg-red-50 text-red-900 border-red-200',
        'warning' => 'bg-amber-50 text-amber-900 border-amber-200',
        default => 'bg-brand-50 text-brand-900 border-brand-200',
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    role="alert"
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$styles]) }}
>
    <div class="min-w-0 flex-1">{{ $slot }}</div>

    <button
        type="button"
        @click="show = false"
        class="-mr-1 shrink-0 rounded p-1 opacity-70 transition-opacity hover:opacity-100"
        aria-label="Dismiss"
    >
        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
        </svg>
    </button>
</div>
