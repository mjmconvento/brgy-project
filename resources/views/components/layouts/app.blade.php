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
<div x-data="{ sidebar: false }" class="min-h-full">
    {{-- Mobile backdrop --}}
    <div
        x-show="sidebar"
        x-transition.opacity
        @click="sidebar = false"
        class="fixed inset-0 z-30 bg-slate-900/60 lg:hidden"
        aria-hidden="true"
        style="display: none"
    ></div>

    {{-- Sidebar --}}
    <aside
        x-show="sidebar"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-brand-900 text-brand-100 lg:flex! lg:translate-x-0"
        style="display: none"
    >
        <div class="flex h-16 shrink-0 items-center gap-2 px-5">
            <span class="flex size-9 items-center justify-center rounded-lg bg-brand-700 text-sm font-bold text-white">BP</span>
            <span class="truncate text-base font-semibold text-white">{{ config('app.name') }}</span>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Main">
            <a
                href="{{ route('dashboard') }}"
                @class([
                    'flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
                    'bg-brand-700 text-white' => request()->routeIs('dashboard'),
                    'text-brand-100 hover:bg-brand-800 hover:text-white' => ! request()->routeIs('dashboard'),
                ])
                @if (request()->routeIs('dashboard')) aria-current="page" @endif
            >
                Dashboard
            </a>
            <a
                href="{{ route('constituents.index') }}"
                @class([
                    'flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
                    'bg-brand-700 text-white' => request()->routeIs('constituents.*'),
                    'text-brand-100 hover:bg-brand-800 hover:text-white' => ! request()->routeIs('constituents.*'),
                ])
                @if (request()->routeIs('constituents.*')) aria-current="page" @endif
            >
                Constituents
            </a>
            <a
                href="{{ route('barangay-captains.index') }}"
                @class([
                    'flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors',
                    'bg-brand-700 text-white' => request()->routeIs('barangay-captains.*'),
                    'text-brand-100 hover:bg-brand-800 hover:text-white' => ! request()->routeIs('barangay-captains.*'),
                ])
                @if (request()->routeIs('barangay-captains.*')) aria-current="page" @endif
            >
                Barangay Captains
            </a>
        </nav>

        <div class="px-5 py-4 text-xs text-brand-300">
            Constituent Profiling System
        </div>
    </aside>

    <div class="lg:pl-64">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
            <button
                type="button"
                @click="sidebar = ! sidebar"
                class="btn btn-ghost -ml-2 px-2 lg:hidden"
                aria-label="Toggle navigation"
            >
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            <div class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-700">
                {{ $title ?? config('app.name') }}
            </div>

            @auth
                <div x-data="{ open: false }" class="relative shrink-0">
                    <button
                        type="button"
                        @click="open = ! open"
                        class="btn btn-secondary"
                        :aria-expanded="open"
                        aria-haspopup="true"
                    >
                        <span class="max-w-40 truncate">{{ auth()->user()->full_name }}</span>
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition.origin.top.right
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                        class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                        style="display: none"
                    >
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-700 hover:bg-red-50">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            @isset($header)
                <div class="mb-6">
                    {{ $header }}
                </div>
            @endisset

            @if (session('status'))
                <div class="mb-6">
                    <x-ui.alert type="success">{{ session('status') }}</x-ui.alert>
                </div>
            @endif

            {{ $slot }}
        </main>

        <footer class="px-4 py-6 text-center text-xs text-slate-500 sm:px-6 lg:px-8">
            &copy; {{ now()->year }} {{ config('app.name') }}
        </footer>
    </div>
</div>
</body>
</html>
