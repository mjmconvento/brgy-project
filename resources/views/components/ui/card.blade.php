@props(['title' => null])

<section {{ $attributes->merge(['class' => 'card']) }}>
    @if (filled($title) || isset($actions))
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ $title }}</h2>

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="px-4 py-4 sm:px-6 sm:py-5">
        {{ $slot }}
    </div>
</section>
