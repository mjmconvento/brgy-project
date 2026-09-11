@props([
    'action',
    'value' => null,
    'placeholder' => 'Search…',
])

<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <label for="search" class="sr-only">Search</label>

    <input
        id="search"
        type="search"
        name="search"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="input w-full sm:w-72"
    >

    <x-ui.button type="submit">Search</x-ui.button>

    @if (filled($value))
        <a href="{{ $action }}" class="btn btn-ghost">Clear</a>
    @endif
</form>
