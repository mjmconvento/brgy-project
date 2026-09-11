@props([
    'name',
    'label',
    'value' => null,
    'required' => false,
    'hint' => null,
    'rows' => 3,
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

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if ($describedBy !== []) aria-describedby="{{ implode(' ', $describedBy) }}" @endif
        {{ $attributes->except('id')->class(['input', 'border-red-400 focus:border-red-500 focus:ring-red-500/30' => $hasError]) }}
    >{{ old($name, $value) }}</textarea>

    @if (filled($hint))
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-xs font-medium text-red-700">{{ $message }}</p>
    @enderror
</div>
