<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'RetailPay') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-100">

            {{-- Brand --}}
            <div class="mb-6 text-center">
                <a href="/" class="inline-flex items-center gap-3">
                    <x-application-logo class="w-12 h-12 fill-current text-blue-800" />
                    <div class="text-left">
                        <div class="text-2xl font-bold text-blue-800 tracking-tight leading-none">
                            RetailPay
                        </div>
                        <div class="text-xs text-slate-500 tracking-wide uppercase mt-1">
                            for KK Wholesalers
                        </div>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md px-6 py-6 bg-white border border-slate-200 shadow-sm overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>

            <div class="mt-6 text-xs text-slate-500">
                &copy; {{ date('Y') }} KK Wholesalers
            </div>
        </div>
    </body>
</html>