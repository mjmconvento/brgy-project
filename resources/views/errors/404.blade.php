<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 font-sans text-slate-900 antialiased">
<div class="flex min-h-full flex-col items-center justify-center px-4 py-12 text-center sm:px-6">
    <span class="flex size-12 items-center justify-center rounded-xl bg-brand-700 text-lg font-bold text-white">BP</span>

    <p class="mt-8 text-sm font-semibold tracking-wide text-brand-700 uppercase">Error 404</p>
    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Page not found</h1>
    <p class="mt-3 max-w-md text-sm text-slate-600">
        The page you were looking for does not exist, or the record has been removed.
    </p>

    <a href="{{ url('/') }}" class="btn btn-primary mt-8">Back to home</a>
</div>
</body>
</html>
