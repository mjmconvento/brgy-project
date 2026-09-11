@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }} &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 font-sans text-slate-900 antialiased">
<div class="flex min-h-full flex-col items-center justify-center px-4 py-12 sm:px-6">
    <div class="w-full max-w-md">
        <div class="mb-8 flex flex-col items-center gap-3">
            <span class="flex size-12 items-center justify-center rounded-xl bg-brand-700 text-lg font-bold text-white">BP</span>
            <span class="text-xl font-semibold text-slate-900">{{ config('app.name') }}</span>
            <span class="text-sm text-slate-500">Barangay Constituent Profiling</span>
        </div>

        @if (session('status'))
            <div class="mb-6">
                <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
            </div>
        @endif

        <div class="card p-6 sm:p-8">
            {{ $slot }}
        </div>

        <p class="mt-8 text-center text-xs text-slate-500">
            &copy; {{ now()->year }} {{ config('app.name') }}
        </p>
    </div>
</div>
</body>
</html>
