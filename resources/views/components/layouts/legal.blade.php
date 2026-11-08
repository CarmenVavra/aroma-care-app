@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1B2447">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <x-theme-script />
    @vite(['resources/css/app.css'])
</head>

<body class="min-h-dvh">
    <header class="border-b border-mist-300 bg-mist-50 dark:border-white/10 dark:bg-night-800">
        <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-5">
            <a href="{{ auth()->check() ? route('stock') : route('login') }}" class="flex items-center gap-2">
                <x-app-icon name="drop" class="size-4 text-sky-300" />
                <span class="font-display text-2xl leading-none">AromaCare</span>
            </a>
            <a href="{{ auth()->check() ? route('stock') : route('login') }}"
                class="flex min-h-11 items-center gap-1 text-sm font-semibold text-violet-700 hover:underline dark:text-cobalt-200">
                <x-app-icon name="chevron-left" class="size-4" />
                {{ auth()->check() ? 'Zum Bestand' : 'Zur Anmeldung' }}
            </a>
        </div>
    </header>

    <main id="main"
        class="mx-auto max-w-3xl px-5 py-10 text-[15px] leading-relaxed [&_a]:font-semibold [&_a]:text-violet-700 [&_a]:underline [&_a]:underline-offset-2 dark:[&_a]:text-cobalt-200 [&_h2]:mt-10 [&_h2]:font-display [&_h2]:text-2xl [&_h3]:mt-6 [&_h3]:text-base [&_h3]:font-bold [&_li]:mt-1 [&_p]:mt-3 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5">
        <h1 class="font-display text-4xl leading-tight lg:text-[42px]">{{ $title }}</h1>
        {{ $slot }}
    </main>

    <x-legal-links class="pb-10" />
</body>

</html>
