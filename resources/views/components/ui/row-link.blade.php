@props(['href'])

<tr
    x-data="{ href: @js($href) }"
    @click="if (! $event.target.closest('a, button, form, input, select, textarea, label')) window.location = href"
    {{ $attributes->merge(['class' => 'cursor-pointer transition-colors hover:bg-slate-50']) }}
>
    {{ $slot }}
</tr>
