@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'neutral',
])

@php
    $valueColor = match ($tone) {
        'positive' => 'text-green-700',
        'negative' => 'text-red-700',
        default => 'text-slate-900',
    };
@endphp

<div {{ $attributes->merge(['class' => 'card p-4']) }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>

    <p class="mt-1 text-2xl font-semibold {{ $valueColor }}">{{ $value }}</p>

    @if (filled($hint))
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
