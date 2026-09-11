<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server error &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 font-sans text-slate-900 antialiased">
<div class="flex min-h-full flex-col items-center justify-center px-4 py-12 text-center sm:px-6">
    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-700 text-lg font-bold text-white">BP</span>

    <p class="mt-8 text-sm font-semibold tracking-wide text-red-700 uppercase">Error 500</p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Something went wrong</h1>
    <p class="mt-3 max-w-md text-sm text-slate-600">
        The server could not complete your request. Please try again in a moment.
    </p>

    <a href="{{ url('/') }}" class="btn btn-primary mt-8">Back to home</a>
</div>
</body>
</html>
