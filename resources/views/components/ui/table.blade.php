<div {{ $attributes->merge(['class' => '-mx-4 overflow-x-auto sm:mx-0']) }}>
    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        {{ $slot }}
    </table>
</div>
