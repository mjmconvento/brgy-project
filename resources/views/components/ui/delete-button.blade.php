@props([
    'action',
    'label' => 'Delete',
    'confirm' => 'Are you sure? This cannot be undone.',
])

<form
    method="POST"
    action="{{ $action }}"
    x-data
    @submit="if (! window.confirm(@js($confirm))) { $event.preventDefault(); }"
    class="inline-block"
>
    @csrf
    @method('DELETE')

    <button type="submit" {{ $attributes->merge(['class' => 'text-sm font-semibold text-red-700 hover:text-red-900']) }}>
        {{ $label }}
    </button>
</form>
