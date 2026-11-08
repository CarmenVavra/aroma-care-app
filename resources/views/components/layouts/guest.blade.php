<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1B2447">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-dvh">
    <main class="flex min-h-dvh flex-col items-center justify-center px-5 py-10">
        <a href="{{ route('login') }}" class="mb-8 flex items-center gap-2">
            <x-app-icon name="drop" class="size-5 text-sky-300" />
            <span class="font-display text-[34px] leading-none">AromaCare</span>
        </a>

        <div class="w-full max-w-sm rounded-(--radius-card) border border-mist-300 bg-mist-50 p-6 shadow-soft dark:border-white/10 dark:bg-night-800">
            {{ $slot }}
        </div>

        <x-legal-links class="mt-6" />
    </main>

    @livewireScriptConfig
</body>

</html>
