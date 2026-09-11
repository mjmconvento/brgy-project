@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        <h1 class="truncate text-2xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>

        @if (filled($subtitle))
            <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
