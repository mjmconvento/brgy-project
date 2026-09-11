@props(['color' => 'gray'])

@php
    $styles = match ($color) {
        'green' => 'bg-green-50 text-green-800 border-green-200',
        'red' => 'bg-red-50 text-red-800 border-red-200',
        'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
        'blue' => 'bg-brand-50 text-brand-800 border-brand-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$styles]) }}>{{ $slot }}</span>
