@props(['message'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 px-6 py-12 text-center']) }}>
    <svg class="size-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5v9a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 16.5v-9M3.75 7.5A2.25 2.25 0 0 1 6 5.25h12a2.25 2.25 0 0 1 2.25 2.25M8.25 11.25h7.5" />
    </svg>

    <p class="text-sm text-slate-600">{{ $message }}</p>

    @isset($action)
        <div class="mt-1">{{ $action }}</div>
    @endisset
</div>
