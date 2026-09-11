@props([
    'variant' => 'primary',
    'type' => 'submit',
    'href' => null,
])

@php
    $classes = 'btn '.match ($variant) {
        'secondary' => 'btn-secondary',
        'danger' => 'btn-danger',
        'ghost' => 'btn-ghost',
        default => 'btn-primary',
    };
@endphp

@if (filled($href))
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
