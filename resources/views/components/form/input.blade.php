@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $describedBy = array_filter([
        filled($hint) ? $id.'-hint' : null,
        $hasError ? $id.'-error' : null,
    ]);
@endphp

<div>
    <label for="{{ $id }}" class="label">
        {{ $label }}
        @if ($required)
            <span class="text-red-600" aria-hidden="true">*</span>
        @endif
    </label>

    <input
        id="{{ $id }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @required($required)
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if ($describedBy !== []) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
        {{ $attributes->except('id')->class(['input', 'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $hasError]) }}
    >

    @if (filled($hint))
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
    @enderror
</div>
