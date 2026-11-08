<div class="mx-auto max-w-4xl px-5 pt-8 pb-10 md:px-8">
    <header>
        <p class="text-sm text-muted dark:text-chalk/70">
            {{ $this->makeableCount }} von {{ $this->categoryCounts[''] }} Rezepten mit deinem Bestand machbar
        </p>
        <h1 class="font-display text-4xl leading-tight lg:text-[42px]">Rezept-Finder</h1>
    </header>

    {{-- General safety notice (design handoff: warning box) --}}
    <div class="mt-5 flex gap-3 rounded-[14px] bg-[#F6E3D8] p-4 text-sm leading-relaxed text-[#7A3B22] dark:bg-[#7A3B22]/30 dark:text-[#F6D5C3]" role="note">
        <x-app-icon name="alert" class="mt-0.5 size-5" />
        <p>
            <strong>Vor der Anwendung lesen.</strong> Die Rezepte sind allgemeine Anregungen für Erwachsene und kein Ersatz für ärztlichen Rat.
            Ätherische Öle nie unverdünnt auf die Haut geben und vorher in der Armbeuge testen.
            In Schwangerschaft und Stillzeit, bei Kindern, Asthma, Epilepsie oder Vorerkrankungen vorher ärztlich abklären.
            Von Augen fernhalten; Katzen und andere Haustiere reagieren empfindlich auf ätherische Öle.
        </p>
    </div>

    {{-- Search --}}
    <div class="mt-5 flex h-12 items-center gap-3 rounded-[14px] border border-mist-300 bg-mist-50 px-4 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-cobalt-500 dark:border-white/10 dark:bg-night-800">
        <x-app-icon name="search" class="text-muted dark:text-chalk/70" />
        <label for="recipe-search" class="sr-only">Rezepte suchen</label>
        <input id="recipe-search" type="search" wire:model.live.debounce.300ms="search"
            placeholder="Rezept, Anliegen oder Zutat suchen" autocomplete="off"
            class="h-full min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-muted dark:placeholder:text-chalk/60">
    </div>

    {{-- Category chips --}}
    <div class="-mx-5 mt-4 overflow-x-auto px-5 [scrollbar-width:none] md:mx-0 md:px-0" role="group" aria-label="Nach Anliegen filtern">
        <div class="flex w-max gap-2">
            @php
                $chips = ['' => 'Alle'];
                foreach ($categories as $category) {
                    $chips[$category->value] = $category->label();
                }
            @endphp
            @foreach ($chips as $value => $label)
                <button type="button" wire:click="$set('selectedCategory', '{{ $value }}')"
                    aria-pressed="{{ $selectedCategory === $value ? 'true' : 'false' }}" @class([
                        'flex h-[38px] items-center gap-2 rounded-full px-4 text-[15px] font-semibold whitespace-nowrap transition-colors',
                        'bg-navy-900 text-white dark:bg-chalk dark:text-navy-900' => $selectedCategory === $value,
                        'border border-mist-300 bg-mist-50 hover:bg-mist-300/50 dark:border-white/10 dark:bg-night-800' => $selectedCategory !== $value,
                    ])>
                    {{ $label }}
                    <span class="font-normal opacity-80">{{ $this->categoryCounts[$value] ?? 0 }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Filters: makeable only, product --}}
    <div class="mt-4 flex flex-wrap items-center gap-2">
        <button type="button" role="switch" wire:click="$toggle('onlyMakeable')" aria-checked="{{ $onlyMakeable ? 'true' : 'false' }}"
            @class([
                'flex h-[38px] items-center gap-2 rounded-full px-4 text-sm font-semibold',
                'bg-[#DCE5FB] text-[#1E3A8A] dark:bg-cobalt-500/25 dark:text-cobalt-200' => $onlyMakeable,
                'border border-mist-300 dark:border-white/10' => ! $onlyMakeable,
            ])>
            @if ($onlyMakeable)
                <x-app-icon name="check" class="size-4" />
            @endif
            Nur mit meinem Bestand machbar
        </button>

        @if ($this->filterProduct)
            <span class="flex h-[38px] items-center gap-1 rounded-full bg-navy-900 pr-1 pl-4 text-sm font-semibold text-white dark:bg-chalk dark:text-navy-900">
                Mit {{ $this->filterProduct->name }}
                <button type="button" wire:click="clearProduct" aria-label="Filter „{{ $this->filterProduct->name }}“ entfernen"
                    class="grid size-8 place-items-center rounded-full hover:bg-white/15 dark:hover:bg-navy-900/10">
                    <x-app-icon name="close" class="size-4" />
                </button>
            </span>
        @endif
    </div>

    {{-- Recipe list --}}
    <ul class="mt-5 space-y-3 transition-opacity" aria-label="Rezepte" wire:loading.class="opacity-60"
        wire:target="search, selectedCategory, onlyMakeable, clearProduct">
        @forelse ($this->recipes as $recipe)
            @php
                $availability = $this->availability[$recipe->id];
                $panelId = 'recipe-panel-'.$recipe->id;
            @endphp
            <li wire:key="recipe-{{ $recipe->id }}" x-data="{ open: false }"
                class="rounded-(--radius-card) border border-mist-300 bg-mist-50 shadow-soft dark:border-white/10 dark:bg-night-800">
                <h2>
                    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="{{ $panelId }}"
                        class="flex w-full items-start gap-3 p-4 text-left md:p-5">
                        <span class="min-w-0 flex-1">
                            <span class="block font-display text-[22px] leading-tight">{{ $recipe->title }}</span>
                            <span class="mt-0.5 block text-sm text-muted dark:text-chalk/70">{{ $recipe->purpose }}</span>
                            <span class="mt-2.5 flex flex-wrap gap-1.5">
                                <span class="rounded-xl bg-mist-100 px-2.5 py-1 text-xs font-semibold dark:bg-white/10">
                                    {{ $recipe->method->label() }} · {{ $recipe->yield }}
                                </span>
                                @if ($recipe->dilution_percent !== null)
                                    <span class="rounded-xl bg-mist-100 px-2.5 py-1 text-xs font-semibold dark:bg-white/10">
                                        ≈ {{ App\Models\Product::formatNumber($recipe->dilution_percent) }} % Verdünnung
                                    </span>
                                @endif
                                @if ($availability['complete'])
                                    <span class="inline-flex items-center gap-1 rounded-xl bg-[#DCE5FB] px-2.5 py-1 text-xs font-bold text-[#1E3A8A] dark:bg-cobalt-500/25 dark:text-cobalt-200">
                                        <x-app-icon name="check" class="size-3.5" /> Alles vorrätig
                                    </span>
                                @else
                                    <span class="rounded-xl bg-[#DCCFF3] px-2.5 py-1 text-xs font-bold text-[#3F1F85] dark:bg-violet-500/30 dark:text-[#CDB7F5]">
                                        Fehlt: {{ implode(', ', $availability['missing']) }}
                                    </span>
                                @endif
                            </span>
                        </span>
                        <x-app-icon name="chevron-down" class="mt-1.5 transition-transform" ::class="open && 'rotate-180'" />
                    </button>
                </h2>

                <div id="{{ $panelId }}" x-show="open" x-collapse x-cloak>
                    <div class="border-t border-mist-300 px-4 pt-4 pb-5 md:px-5 dark:border-white/10">
                        <h3 class="mb-2 text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">Zutaten</h3>
                        <ul class="divide-y divide-mist-300 dark:divide-white/10">
                            @foreach ($recipe->ingredients as $ingredient)
                                @php($inStock = $availability['stock'][$ingredient->id] ?? null)
                                <li class="flex items-center gap-3 py-2.5 text-[15px]">
                                    <span @class([
                                        'grid size-6 shrink-0 place-items-center rounded-full',
                                        'bg-[#DCE5FB] text-[#1E3A8A] dark:bg-cobalt-500/25 dark:text-cobalt-200' => $inStock,
                                        'border-[1.5px] border-dashed border-violet-500 dark:border-violet-400' => ! $inStock,
                                    ])>
                                        @if ($inStock)
                                            <x-app-icon name="check" class="size-3.5" />
                                        @endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="font-semibold">{{ $ingredient->formatted_amount }}</span> {{ $ingredient->name }}
                                        @if ($ingredient->optional)
                                            <span class="text-muted dark:text-chalk/70">(optional)</span>
                                        @endif
                                        <span class="block text-[13px] text-muted dark:text-chalk/70">
                                            @if ($inStock)
                                                Vorrätig: {{ $inStock->name }} · {{ $inStock->formatted_quantity }}
                                            @else
                                                Nicht im Bestand
                                            @endif
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>

                        <h3 class="mt-5 mb-2 text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">Zubereitung</h3>
                        <ol class="list-decimal space-y-1.5 pl-5 text-[15px] leading-relaxed marker:font-semibold marker:text-violet-700 dark:marker:text-cobalt-200">
                            @foreach ($recipe->steps as $step)
                                <li>{{ $step }}</li>
                            @endforeach
                        </ol>

                        @if ($recipe->safety_note)
                            <p class="mt-4 rounded-[14px] bg-[#F6E3D8] p-3.5 text-sm leading-relaxed text-[#7A3B22] dark:bg-[#7A3B22]/30 dark:text-[#F6D5C3]">
                                <strong>Hinweis:</strong> {{ $recipe->safety_note }}
                            </p>
                        @endif
                    </div>
                </div>
            </li>
        @empty
            <li class="rounded-(--radius-card) border border-mist-300 bg-mist-50 py-14 text-center dark:border-white/10 dark:bg-night-800">
                <p class="font-display text-2xl">Keine Rezepte gefunden</p>
                <p class="mt-2 text-sm text-muted dark:text-chalk/70">Passe Suche oder Filter an.</p>
            </li>
        @endforelse
    </ul>
</div>
