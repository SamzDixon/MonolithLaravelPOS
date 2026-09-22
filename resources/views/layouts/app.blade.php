<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RetailPay') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="font-sans antialiased bg-slate-100">
        <div x-data="{
                sidebarOpen: false,
                openStock: {{ request()->routeIs('stock-levels.*') || request()->routeIs('stock-movements.*') ? 'true' : 'false' }},
                openSetup: {{ request()->routeIs('branches.*') || request()->routeIs('stores.*') || request()->routeIs('products.*') || request()->routeIs('users.*') ? 'true' : 'false' }}
             }"
             @keydown.escape.window="sidebarOpen = false"
             class="min-h-screen">

            @include('layouts.navigation')

            <div class="lg:pl-64 flex flex-col min-h-screen">
                {{-- Mobile topbar --}}
                <div class="lg:hidden sticky top-0 z-30 bg-blue-900 border-b border-blue-800 px-4 h-14 flex items-center gap-3">
                    <button type="button" @click="sidebarOpen = true"
                            class="p-2 text-blue-100 hover:text-white hover:bg-blue-800/60 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span class="font-semibold text-white tracking-tight">RetailPay</span>
                </div>

                @isset($header)
                    <header class="bg-white border-b border-slate-200">
                        <div class="px-6 py-4">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>

            <x-toast-stack />
        </div>
    </body>
</html>