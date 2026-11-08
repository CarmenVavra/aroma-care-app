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

@php
    $onStock = request()->routeIs('stock');
    $onProfile = request()->routeIs('profile');
    $onRecipes = request()->routeIs('recipes');
    $sideItem = 'flex h-11 items-center gap-3 rounded-xl px-3.5 text-left text-[15px]';
@endphp

<body class="min-h-dvh">
    <a href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-xl focus:bg-navy-900 focus:px-4 focus:py-2 focus:text-white">
        Zum Inhalt springen
    </a>

    <div class="lg:flex">
        {{-- Desktop sidebar --}}
        <aside class="hidden lg:sticky lg:top-0 lg:flex lg:h-dvh lg:w-60 lg:shrink-0 lg:flex-col lg:bg-navy-900 lg:px-[18px] lg:py-7 lg:text-white"
            aria-label="Hauptnavigation">
            <a href="{{ route('stock') }}" class="mb-8 flex items-center gap-2 px-2.5">
                <x-app-icon name="drop" class="size-4 text-sky-300" />
                <span class="font-display text-[28px] leading-none">AromaCare</span>
            </a>

            <nav class="flex flex-col gap-1.5">
                <a href="{{ route('stock') }}" @if ($onStock) aria-current="page" @endif
                    @class([$sideItem, 'bg-cobalt-200/20 font-bold' => $onStock, 'font-medium text-white/85 hover:bg-white/10' => ! $onStock])>Bestand</a>

                @if ($onStock)
                    <button type="button" x-data @click="$dispatch('open-scanner')"
                        class="{{ $sideItem }} font-medium text-white/85 hover:bg-white/10">Scanner</button>
                @else
                    <a href="{{ route('stock', ['scan' => 1]) }}" class="{{ $sideItem }} font-medium text-white/85 hover:bg-white/10">Scanner</a>
                @endif

                <a href="{{ route('recipes') }}" @if ($onRecipes) aria-current="page" @endif
                    @class([$sideItem, 'bg-cobalt-200/20 font-bold' => $onRecipes, 'font-medium text-white/85 hover:bg-white/10' => ! $onRecipes])>Rezept-Finder</a>

                <a href="{{ route('profile') }}" @if ($onProfile) aria-current="page" @endif
                    @class([$sideItem, 'bg-cobalt-200/20 font-bold' => $onProfile, 'font-medium text-white/85 hover:bg-white/10' => ! $onProfile])>
                    Profil
                </a>
            </nav>

            <div class="mt-auto space-y-5">
                @if ($onStock)
                    <button type="button" x-data @click="$dispatch('create-product')"
                        class="flex h-[50px] w-full items-center justify-center gap-2 rounded-2xl bg-sky-300 text-[15px] font-bold text-navy-900 hover:bg-sky-300/90">
                        <x-app-icon name="plus" class="size-4" /> Neues Produkt
                    </button>
                @else
                    <a href="{{ route('stock', ['neu' => 1]) }}"
                        class="flex h-[50px] w-full items-center justify-center gap-2 rounded-2xl bg-sky-300 text-[15px] font-bold text-navy-900 hover:bg-sky-300/90">
                        <x-app-icon name="plus" class="size-4" /> Neues Produkt
                    </a>
                @endif

                <div x-data="themeSwitch"
                    class="flex items-center justify-between px-2.5 text-[13px]">
                    <span id="dark-mode-label">Dark Mode</span>
                    <button type="button" role="switch" :aria-checked="dark.toString()"
                        aria-labelledby="dark-mode-label"
                        @click="toggle()"
                        class="relative h-6 w-11 rounded-full bg-white/20 transition-colors"
                        :class="dark && 'bg-sky-300'">
                        <span class="absolute left-0.5 top-0.5 size-5 rounded-full bg-white transition-transform"
                            :class="dark && 'translate-x-5'"></span>
                    </button>
                </div>

                <nav aria-label="Rechtliches" class="flex gap-4 px-2.5 text-xs text-white/70">
                    <a href="{{ route('legal.imprint') }}" class="hover:text-white hover:underline">Impressum</a>
                    <a href="{{ route('legal.privacy') }}" class="hover:text-white hover:underline">Datenschutz</a>
                </nav>
            </div>
        </aside>

        <main id="main" class="min-w-0 flex-1 pb-28 lg:pb-0">
            {{ $slot }}
        </main>
    </div>

    {{-- Mobile / tablet bottom navigation (PWA) --}}
    @php
        $navItem = 'flex flex-col items-center gap-1 text-xs whitespace-nowrap';
        $pill = 'grid h-[30px] w-[52px] place-items-center rounded-full';
    @endphp
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-mist-300 bg-mist-50/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden dark:border-white/10 dark:bg-night-800/95"
        aria-label="Hauptnavigation">
        <ul class="mx-auto grid h-[84px] max-w-md grid-cols-4 items-start px-3 pt-2">
            <li class="grid place-items-center">
                <a href="{{ route('stock') }}" @if ($onStock) aria-current="page" @endif
                    @class([$navItem, 'font-bold' => $onStock, 'font-medium text-muted dark:text-chalk/70' => ! $onStock])>
                    <span @class([$pill, 'bg-[#DCE5FB] dark:bg-cobalt-200/15' => $onStock])><x-app-icon name="grid" /></span>
                    Bestand
                </a>
            </li>
            <li class="-mt-7 grid place-items-center">
                @php($scanIcon = 'grid size-[54px] place-items-center rounded-full bg-sky-300 text-navy-900 shadow-lg shadow-violet-500/25 ring-4 ring-mist-50 dark:ring-night-800')
                @if ($onStock)
                    <button type="button" x-data @click="$dispatch('open-scanner')" class="{{ $navItem }} font-bold">
                        <span class="{{ $scanIcon }}"><x-app-icon name="scan" class="size-6" /></span>
                        Scanner
                    </button>
                @else
                    <a href="{{ route('stock', ['scan' => 1]) }}" class="{{ $navItem }} font-bold">
                        <span class="{{ $scanIcon }}"><x-app-icon name="scan" class="size-6" /></span>
                        Scanner
                    </a>
                @endif
            </li>
            <li class="grid place-items-center">
                <a href="{{ route('recipes') }}" @if ($onRecipes) aria-current="page" @endif
                    @class([$navItem, 'font-bold' => $onRecipes, 'font-medium text-muted dark:text-chalk/70' => ! $onRecipes])>
                    <span @class([$pill, 'bg-[#DCE5FB] dark:bg-cobalt-200/15' => $onRecipes])><x-app-icon name="recipe" /></span>
                    Rezept-Finder
                </a>
            </li>
            <li class="grid place-items-center">
                <a href="{{ route('profile') }}" @if ($onProfile) aria-current="page" @endif
                    @class([$navItem, 'font-bold' => $onProfile, 'font-medium text-muted dark:text-chalk/70' => ! $onProfile])>
                    <span @class([$pill, 'bg-[#DCE5FB] dark:bg-cobalt-200/15' => $onProfile])><x-app-icon name="user" /></span>
                    Profil
                </a>
            </li>
        </ul>
    </nav>

    <x-toast />

    @livewireScriptConfig
</body>

</html>
