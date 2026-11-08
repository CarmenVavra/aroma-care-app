<div x-data="{ confirmDelete: null }" @create-product.window="$wire.createProduct()" class="lg:flex lg:min-h-dvh">

    {{-- ================================================================
         Liste
         ================================================================ --}}
    <section class="min-w-0 flex-1 px-5 pt-8 md:px-8" aria-labelledby="page-title">
        <header class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted dark:text-chalk/70">
                    {{ $greeting }}, {{ auth()->user()->name }}
                    <span class="hidden md:inline">· {{ $this->categoryCounts[''] }} Produkte · {{ $this->lowStockCount }} fast leer</span>
                </p>
                <h1 id="page-title" class="font-display text-4xl leading-tight lg:text-[42px]">Bestand</h1>
            </div>
            <a href="{{ route('profile') }}" aria-label="Profil von {{ auth()->user()->name }}"
                class="grid size-11 shrink-0 place-items-center rounded-full bg-navy-900 text-lg font-bold text-white md:hidden dark:bg-chalk dark:text-navy-900">
                <span aria-hidden="true">{{ auth()->user()->initial }}</span>
            </a>
            <button type="button" wire:click="createProduct"
                class="hidden h-11 items-center gap-2 rounded-2xl bg-navy-900 px-4 text-sm font-bold text-white hover:bg-navy-900/90 md:flex lg:hidden dark:bg-chalk dark:text-navy-900">
                <x-app-icon name="plus" class="size-4" /> Neues Produkt
            </button>
        </header>

        {{-- Search --}}
        <div class="mt-5 flex h-12 items-center gap-3 rounded-[14px] border border-mist-300 bg-mist-50 pr-2 pl-4 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-cobalt-500 dark:border-white/10 dark:bg-night-800">
            <x-app-icon name="search" class="text-muted dark:text-chalk/70" />
            <label for="product-search" class="sr-only">Produkte suchen</label>
            <input id="product-search" type="search" wire:model.live.debounce.300ms="search"
                placeholder="Öl, Wirkung oder Barcode suchen" autocomplete="off"
                class="h-full min-w-0 flex-1 bg-transparent text-base outline-none placeholder:text-muted dark:placeholder:text-chalk/60">
            <button type="button" @click="$dispatch('open-scanner')" aria-label="Barcode scannen"
                class="grid size-9 place-items-center rounded-[10px] bg-mist-100 hover:bg-mist-300 dark:bg-white/10">
                <x-app-icon name="scan" class="size-[18px]" />
            </button>
        </div>

        {{-- Category chips --}}
        <div class="-mx-5 mt-4 overflow-x-auto px-5 [scrollbar-width:none] md:mx-0 md:px-0" role="group" aria-label="Nach Kategorie filtern">
            <div class="flex w-max gap-2">
                @php
                    $chips = ['' => 'Alle'];
                    foreach ($categories as $category) {
                        $chips[$category->value] = $category->pluralLabel();
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

        {{-- Low stock band --}}
        @if ($this->lowStockCount > 0 || $lowStockOnly)
            <button type="button" wire:click="$toggle('lowStockOnly')" aria-pressed="{{ $lowStockOnly ? 'true' : 'false' }}"
                class="mt-4 flex h-11 w-full items-center justify-between rounded-xl bg-[#DCCFF3] px-4 text-[15px] font-semibold text-[#3F1F85] hover:bg-[#d2c2ef] dark:bg-violet-500/30 dark:text-[#CDB7F5]">
                <span>{{ $this->lowStockCount }} {{ $this->lowStockCount === 1 ? 'Produkt' : 'Produkte' }} fast leer</span>
                <span class="flex items-center gap-1">{{ $lowStockOnly ? 'Alle zeigen' : 'Liste' }}
                    <x-app-icon name="chevron-right" class="size-4" /></span>
            </button>
        @endif

        {{-- Product list --}}
        <div class="mt-3 transition-opacity md:mt-5 md:rounded-(--radius-card) md:border md:border-mist-300 md:bg-mist-50 md:px-5 md:shadow-soft dark:md:border-white/10 dark:md:bg-night-800"
            wire:loading.class="opacity-60" wire:target="search, selectedCategory, lowStockOnly, gotoPage, nextPage, previousPage">

            <div class="hidden grid-cols-[56px_1.6fr_1fr_1.3fr_110px] items-center gap-4 border-b border-mist-300 py-3 text-xs font-bold tracking-[.08em] text-muted uppercase md:grid dark:border-white/10 dark:text-chalk/70"
                aria-hidden="true">
                <span></span><span>Produkt</span><span>Kategorie</span><span>Bestand</span><span class="text-right">Status</span>
            </div>

            <ul class="divide-y divide-mist-300 dark:divide-white/10" aria-label="Produkte">
                @forelse ($this->products as $product)
                    <li wire:key="product-{{ $product->id }}">
                        <button type="button" wire:click="showProduct({{ $product->id }})"
                            aria-current="{{ $selectedProductId === $product->id ? 'true' : 'false' }}"
                            @class([
                                'grid w-full grid-cols-[48px_1fr_auto] items-center gap-3.5 py-3 text-left md:grid-cols-[56px_1.6fr_1fr_1.3fr_110px] md:gap-4',
                                'md:-mx-5 md:w-[calc(100%+2.5rem)] md:bg-mist-100 md:px-5 dark:md:bg-white/5' => $selectedProductId === $product->id,
                            ])>
                            <x-category-tile :product="$product" />

                            <span class="min-w-0">
                                <span class="block truncate text-base font-semibold">{{ $product->name }}</span>
                                <span class="block truncate text-[13px] text-muted dark:text-chalk/70">
                                    {{ collect([$product->brand, $product->formatted_initial_quantity])->filter()->join(' · ') }}
                                </span>
                                <x-stock-bar :product="$product" class="mt-2 md:hidden" />
                            </span>

                            <span class="hidden text-[15px] md:block">{{ $product->category->label() }}</span>

                            <span class="hidden md:block">
                                <span class="block text-[13px] font-semibold">{{ $product->formatted_quantity }}</span>
                                <x-stock-bar :product="$product" class="mt-1.5 max-w-44" />
                            </span>

                            <span class="flex flex-col items-end gap-1.5">
                                <x-stock-badge :status="$product->stock_status" />
                                <span class="text-[13px] text-muted md:hidden dark:text-chalk/70">{{ $product->formatted_quantity }}</span>
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="py-14 text-center">
                        <p class="font-display text-2xl">Keine Produkte gefunden</p>
                        <p class="mt-2 text-sm text-muted dark:text-chalk/70">
                            @if ($search !== '' || $selectedCategory !== '' || $lowStockOnly)
                                Passe Suche oder Filter an – oder lege ein neues Produkt an.
                            @else
                                Scanne einen Barcode oder lege dein erstes Produkt manuell an.
                            @endif
                        </p>
                        <div class="mt-5 flex justify-center gap-3">
                            <button type="button" @click="$dispatch('open-scanner')"
                                class="h-11 rounded-2xl bg-sky-300 px-5 text-sm font-bold text-navy-900">Scannen</button>
                            <button type="button" wire:click="createProduct"
                                class="h-11 rounded-2xl border-[1.5px] border-navy-900 px-5 text-sm font-bold dark:border-chalk">Manuell anlegen</button>
                        </div>
                    </li>
                @endforelse
            </ul>
        </div>

        <div class="py-6">
            {{ $this->products->links() }}
        </div>
    </section>

    {{-- ================================================================
         Detail: bottom sheet (mobile/tablet), side panel (desktop)
         ================================================================ --}}
    @if ($product = $this->selectedProduct)
        <div wire:key="detail-{{ $product->id }}" class="fixed inset-0 z-40 bg-night-900/50 lg:hidden"
            wire:click="closeDetail" aria-hidden="true"></div>

        <aside wire:key="detail-panel-{{ $product->id }}" x-data x-trap.inert="window.innerWidth < 1024" x-scroll-lock="window.innerWidth < 1024"
            @keydown.escape.window="$wire.closeDetail()" role="dialog" aria-modal="true" aria-labelledby="detail-title"
            class="fixed inset-x-0 bottom-0 z-50 max-h-[92dvh] overflow-y-auto rounded-t-[28px] bg-mist-50 px-5 pt-3 pb-[max(env(safe-area-inset-bottom),1.5rem)] shadow-soft md:inset-x-auto md:top-0 md:right-0 md:max-h-none md:w-[420px] md:rounded-none md:border-l md:border-mist-300 md:px-7 md:pt-8 lg:sticky lg:top-0 lg:z-auto lg:h-dvh lg:shadow-none dark:bg-night-800 dark:md:border-white/10">

            <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-lilac-300 md:hidden" aria-hidden="true"></div>

            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">
                    {{ $product->category->label() }}
                </p>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="editProduct({{ $product->id }})"
                        class="flex h-9 items-center gap-1.5 rounded-xl border-[1.5px] border-navy-900 px-3 text-sm font-semibold dark:border-chalk">
                        <x-app-icon name="edit" class="size-4" /> Bearbeiten
                    </button>
                    <button type="button" wire:click="closeDetail" aria-label="Detailansicht schließen"
                        class="grid size-9 place-items-center rounded-full bg-mist-100 hover:bg-mist-300 dark:bg-white/10">
                        <x-app-icon name="close" class="size-4" />
                    </button>
                </div>
            </div>

            @if ($openedByScan)
                <p class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-[#DCE5FB] px-3 py-1.5 text-sm font-bold text-[#1E3A8A] dark:bg-cobalt-500/25 dark:text-cobalt-200"
                    role="status">
                    <x-app-icon name="check" class="size-4" /> Produkt erkannt
                </p>
            @endif

            <div class="mt-4 flex items-start gap-4">
                <x-category-tile :product="$product" size="lg" />
                <div class="min-w-0">
                    <h2 id="detail-title" class="font-display text-[34px] leading-tight">{{ $product->name }}</h2>
                    @if ($latin = $product->essentialOilDetail?->latin_name ?? $product->carrierOilDetail?->latin_name)
                        <p class="font-display text-base text-cobalt-700 italic dark:text-cobalt-200">{{ $latin }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($product->brand)
                            <span class="rounded-xl bg-navy-900 px-2.5 py-1 text-xs font-bold text-white dark:bg-chalk dark:text-navy-900">{{ $product->brand }}</span>
                        @endif
                        @if ($product->carrierOilDetail?->certification)
                            <span class="rounded-xl bg-[#E6E0F6] px-2.5 py-1 text-xs font-bold text-[#4B2E8F]">{{ $product->carrierOilDetail->certification }}</span>
                        @endif
                        <x-stock-badge :status="$product->stock_status" />
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <p class="text-sm">
                    <span class="font-bold">Bestand {{ $product->formatted_quantity }}</span>
                    <span class="text-muted dark:text-chalk/70">/ {{ $product->formatted_initial_quantity }}</span>
                </p>
                <x-stock-bar :product="$product" height="h-1.5" class="mt-2" />
                <dl class="mt-3 grid grid-cols-2 gap-2 text-[13px]">
                    @if ($product->barcode)
                        <div><dt class="text-muted dark:text-chalk/70">Code</dt><dd class="font-semibold break-all">{{ $product->barcode }}</dd></div>
                    @endif
                    @if ($product->expires_at)
                        <div><dt class="text-muted dark:text-chalk/70">Haltbar bis</dt><dd class="font-semibold">{{ $product->expires_at->format('m/Y') }}</dd></div>
                    @endif
                </dl>
            </div>

            @if ($detail = $product->essentialOilDetail)
                @if (count($detail->effects_mental ?? []) || count($detail->effects_physical ?? []))
                    <h3 class="mt-6 mb-2 text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">Wirkung</h3>
                    <dl class="space-y-2">
                        @foreach (['effects_mental' => 'Psyche', 'effects_physical' => 'Körper'] as $field => $label)
                            @if (count($detail->{$field} ?? []))
                                <div>
                                    <dt class="sr-only">{{ $label }}</dt>
                                    <dd>
                                        <ul class="flex flex-wrap gap-1.5" aria-label="{{ $label }}">
                                            @foreach ($detail->{$field} as $tag)
                                                <li @class([
                                                    'rounded-[15px] px-3 py-1 text-sm',
                                                    'bg-sky-100 text-[#1E3A8A] dark:bg-lilac-300/15 dark:text-lilac-300' => $field === 'effects_mental',
                                                    'bg-[#E6E0F6] text-[#4B2E8F] dark:bg-sky-300/20 dark:text-[#B79DF0]' => $field === 'effects_physical',
                                                ])>{{ $tag }}</li>
                                            @endforeach
                                        </ul>
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                    </dl>
                @endif

                @if (count($detail->usage_types ?? []))
                    <h3 class="mt-6 mb-2 text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">Anwendung</h3>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($detail->usage_types as $usage)
                            <li class="flex h-9 items-center gap-1 rounded-full bg-navy-900 px-3.5 text-sm font-semibold text-white dark:bg-chalk dark:text-navy-900">
                                <x-app-icon name="check" class="size-3.5" /> {{ $usage }}
                            </li>
                        @endforeach
                    </ul>
                    @if ($detail->dilution_hint)
                        <p class="mt-2 text-[13px] text-muted dark:text-chalk/70">Hautverdünnung {{ $detail->dilution_hint }}</p>
                    @endif
                @endif
            @endif

            @if ($detail = $product->carrierOilDetail)
                <div class="mt-6 space-y-3">
                    @if ($detail->comedogenic_rating !== null)
                        <x-comedo-scale :value="$detail->comedogenic_rating" :label="$detail->comedogenicLabel()" />
                    @endif
                    @if ($detail->absorption)
                        <x-speed-gauge :absorption="$detail->absorption" />
                    @endif
                </div>

                @if (count($detail->use_areas ?? []))
                    <h3 class="mt-6 mb-2 text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">Anwendungsgebiete</h3>
                    <ul class="flex flex-wrap gap-2">
                        @foreach ($detail->use_areas as $area)
                            <li class="flex h-[38px] items-center rounded-full border border-mist-300 px-4 text-sm font-semibold dark:border-white/10">{{ $area }}</li>
                        @endforeach
                    </ul>
                @endif
            @endif

            @if ($this->selectedProductRecipes->isNotEmpty())
                <h3 class="mt-6 mb-2 text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">Rezeptideen</h3>
                <ul class="divide-y divide-mist-300 rounded-[18px] bg-mist-100 px-4 dark:divide-white/10 dark:bg-white/5">
                    @foreach ($this->selectedProductRecipes->take(3) as $recipe)
                        <li class="py-3">
                            <span class="block font-semibold">{{ $recipe->title }}</span>
                            <span class="block text-[13px] text-muted dark:text-chalk/70">{{ $recipe->method->label() }} · {{ $recipe->yield }} · {{ $recipe->purpose }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('recipes', ['produkt' => $product->id]) }}"
                    class="mt-2 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-violet-700 hover:underline dark:text-cobalt-200">
                    {{ $this->selectedProductRecipes->count() === 1 ? 'Rezept' : 'Alle '.$this->selectedProductRecipes->count().' Rezepte' }} im Rezept-Finder
                    <x-app-icon name="chevron-right" class="size-4" />
                </a>
            @endif

            <div class="mt-8">
                <button type="button" @click="confirmDelete = {{ $product->id }}"
                    class="flex h-[50px] w-full items-center justify-center gap-2 rounded-2xl border-[1.5px] border-violet-700 font-semibold text-violet-700 hover:bg-violet-700/5 dark:border-violet-400 dark:text-violet-400">
                    <x-app-icon name="trash" class="size-4" /> Produkt löschen
                </button>
            </div>
        </aside>
    @endif

    {{-- ================================================================
         Erfassen / Bearbeiten (modal)
         ================================================================ --}}
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 bg-night-900/50 max-md:hidden" wire:click="closeModal" aria-hidden="true"></div>

        <div x-data x-trap="true" x-scroll-lock @keydown.escape.window="$wire.closeModal()" role="dialog" aria-modal="true"
            aria-labelledby="form-title"
            class="fixed inset-0 z-50 flex flex-col bg-mist-100 md:inset-auto md:top-1/2 md:left-1/2 md:max-h-[90dvh] md:w-full md:max-w-xl md:-translate-x-1/2 md:-translate-y-1/2 md:overflow-hidden md:rounded-[28px] md:shadow-soft dark:bg-night-900">

            <form wire:submit="saveProduct" class="flex min-h-0 flex-1 flex-col" novalidate>
                {{-- Top bar --}}
                <div class="flex items-center justify-between gap-3 px-5 pt-[max(env(safe-area-inset-top),1.25rem)] pb-3 md:pt-5">
                    <button type="button" wire:click="closeModal" aria-label="Formular schließen"
                        class="grid size-11 place-items-center rounded-full hover:bg-mist-300/60 dark:hover:bg-white/10">
                        <x-app-icon name="close" />
                    </button>
                    <h2 id="form-title" class="font-display text-[22px]">
                        {{ $currentProduct->id ? 'Produkt bearbeiten' : 'Neues Produkt' }}
                    </h2>
                    <button type="button" @click="$dispatch('open-scanner')"
                        class="h-9 rounded-full bg-sky-300 px-4 text-[13px] font-bold text-navy-900">Scannen</button>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 pb-6">
                    @if ($suggestionSource)
                        <p class="flex items-start gap-2 rounded-[14px] bg-[#DCE5FB] px-3.5 py-3 text-sm text-[#1E3A8A] dark:bg-cobalt-500/25 dark:text-cobalt-200"
                            role="status">
                            <x-app-icon name="check" class="mt-0.5 size-4" />
                            <span>Vorschlag aus <strong>{{ $suggestionSource }}</strong> – bitte Name, Menge und Kategorie prüfen.</span>
                        </p>
                    @endif

                    {{-- Category segmented control --}}
                    <fieldset>
                        <legend class="sr-only">Kategorie</legend>
                        <div class="grid grid-cols-4 gap-1 rounded-[14px] bg-segment p-1 dark:bg-white/10">
                            @foreach ($categories as $category)
                                <label class="relative">
                                    <input type="radio" class="peer sr-only" name="category" value="{{ $category->value }}"
                                        wire:model.live="currentProduct.category">
                                    <span class="grid h-10 cursor-pointer place-items-center rounded-[10px] text-sm font-semibold text-muted peer-checked:bg-mist-50 peer-checked:text-navy-900 peer-checked:shadow-soft peer-focus-visible:outline-2 peer-focus-visible:outline-cobalt-500 dark:text-chalk/70 dark:peer-checked:bg-night-800 dark:peer-checked:text-chalk">
                                        {{ $category->shortLabel() }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    @php
                        $input = 'h-[46px] w-full rounded-xl border bg-mist-50 px-3.5 text-base dark:bg-night-800 dark:border-white/15';
                        $label = 'mb-1 block text-xs font-semibold text-muted dark:text-chalk/75';
                        $err = 'mt-1 text-[13px] font-medium text-violet-700 dark:text-violet-400';
                    @endphp

                    <div>
                        <label for="f-name" class="{{ $label }}">Name *</label>
                        <input id="f-name" type="text" wire:model="currentProduct.name" required maxlength="120"
                            aria-invalid="{{ $errors->has('currentProduct.name') ? 'true' : 'false' }}" aria-describedby="f-name-error"
                            @class([$input, 'border-violet-500' => $errors->has('currentProduct.name'), 'border-line' => ! $errors->has('currentProduct.name')])>
                        @error('currentProduct.name') <p id="f-name-error" class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="f-brand" class="{{ $label }}">Marke</label>
                        <input id="f-brand" type="text" wire:model="currentProduct.brand" maxlength="120"
                            @class([$input, 'border-line'])>
                        @error('currentProduct.brand') <p class="{{ $err }}">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="f-barcode" class="{{ $label }}">Barcode / QR-Code</label>
                        <input id="f-barcode" type="text" wire:model="currentProduct.barcode" maxlength="{{ App\Support\Barcode::MAX_LENGTH }}"
                            aria-invalid="{{ $errors->has('currentProduct.barcode') ? 'true' : 'false' }}" aria-describedby="f-barcode-error f-barcode-hint"
                            @class([$input, 'border-violet-500' => $errors->has('currentProduct.barcode'), 'border-line' => ! $errors->has('currentProduct.barcode')])>
                        @error('currentProduct.barcode') <p id="f-barcode-error" class="{{ $err }}">{{ $message }}</p> @enderror
                        <p id="f-barcode-hint" class="mt-1 text-xs text-muted dark:text-chalk/70">
                            Name, Marke und Größe zu diesem Code landen ohne Bezug zu deinem Konto im
                            <a href="{{ route('legal.privacy') }}" target="_blank" class="underline">gemeinsamen Katalog</a>.
                        </p>
                    </div>

                    <div class="grid grid-cols-[1fr_0.8fr_1.2fr] gap-2.5">
                        <div>
                            <label for="f-quantity" class="{{ $label }}">Menge *</label>
                            <input id="f-quantity" type="text" inputmode="decimal" wire:model="currentProduct.quantity" required
                                aria-invalid="{{ $errors->has('currentProduct.quantity') ? 'true' : 'false' }}" aria-describedby="f-quantity-error"
                                @class([$input, 'border-violet-500' => $errors->has('currentProduct.quantity'), 'border-line' => ! $errors->has('currentProduct.quantity')])>
                        </div>
                        <div>
                            <label for="f-unit" class="{{ $label }}">Einheit</label>
                            <select id="f-unit" wire:model="currentProduct.unit" @class([$input, 'border-line'])>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit }}">{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="f-expires" class="{{ $label }}">Haltbar bis</label>
                            <input id="f-expires" type="text" inputmode="numeric" placeholder="MM/JJJJ" maxlength="7"
                                wire:model="currentProduct.expires_at"
                                aria-invalid="{{ $errors->has('currentProduct.expires_at') ? 'true' : 'false' }}" aria-describedby="f-expires-error"
                                @class([$input, 'border-violet-500' => $errors->has('currentProduct.expires_at'), 'border-line' => ! $errors->has('currentProduct.expires_at')])>
                        </div>
                    </div>
                    @error('currentProduct.quantity') <p id="f-quantity-error" class="{{ $err }} -mt-2">{{ $message }}</p> @enderror
                    @error('currentProduct.expires_at') <p id="f-expires-error" class="{{ $err }} -mt-2">{{ $message }}</p> @enderror

                    {{-- ---------- Ätherisches Öl ---------- --}}
                    @if ($currentProduct->category === 'essential_oil')
                        <fieldset wire:key="fields-essential" class="space-y-3 rounded-[18px] bg-sky-100 p-3.5 text-[#1E3A8A] dark:bg-cobalt-700/20 dark:text-cobalt-200">
                            <legend class="float-left mb-1 flex w-full items-center gap-2 text-xs font-bold tracking-[.08em] uppercase">
                                <span class="size-2 rounded-full bg-cobalt-500" aria-hidden="true"></span> Nur für ätherische Öle
                            </legend>

                            <div class="clear-both">
                                <label for="f-latin" class="mb-1 block text-xs font-semibold">Botanischer Name</label>
                                <input id="f-latin" type="text" wire:model="currentProduct.latin_name" maxlength="160"
                                    class="{{ $input }} border-line text-navy-900 italic dark:text-chalk">
                            </div>

                            @foreach (['effects_mental' => 'Wirkung · psychisch', 'effects_physical' => 'Wirkung · körperlich'] as $field => $fieldLabel)
                                <div x-data="{ draft: '' }">
                                    <p id="lbl-{{ $field }}" class="mb-1.5 text-xs font-semibold">{{ $fieldLabel }}</p>
                                    <ul class="flex flex-wrap gap-2" aria-labelledby="lbl-{{ $field }}">
                                        @foreach ($currentProduct->{$field} as $i => $tag)
                                            <li wire:key="{{ $field }}-{{ $i }}-{{ $tag }}" @class([
                                                'flex h-9 items-center gap-1.5 rounded-full pr-1.5 pl-3.5 text-sm',
                                                'bg-mist-50 text-navy-900 dark:bg-night-800 dark:text-chalk' => $field === 'effects_mental',
                                                'bg-[#E6E0F6] text-[#4B2E8F]' => $field === 'effects_physical',
                                            ])>
                                                {{ $tag }}
                                                <button type="button" wire:click="removeTag('{{ $field }}', {{ $i }})"
                                                    aria-label="{{ $tag }} entfernen" class="grid size-7 place-items-center rounded-full hover:bg-black/5">
                                                    <x-app-icon name="close" class="size-3.5" />
                                                </button>
                                            </li>
                                        @endforeach
                                        <li class="flex h-9 items-center rounded-full border border-dashed border-cobalt-500 pl-3 dark:border-cobalt-200">
                                            <label for="tag-{{ $field }}" class="sr-only">Tag hinzufügen ({{ $fieldLabel }})</label>
                                            <input id="tag-{{ $field }}" type="text" x-model="draft" maxlength="40" placeholder="+ Tag"
                                                @keydown.enter.prevent="if (draft.trim()) { $wire.addTag('{{ $field }}', draft); draft = '' }"
                                                class="w-24 bg-transparent text-sm outline-none placeholder:text-cobalt-700 dark:placeholder:text-cobalt-200">
                                            <button type="button" aria-label="Tag hinzufügen"
                                                @click="if (draft.trim()) { $wire.addTag('{{ $field }}', draft); draft = '' }"
                                                class="grid size-9 place-items-center rounded-full">
                                                <x-app-icon name="plus" class="size-4" />
                                            </button>
                                        </li>
                                    </ul>
                                    @error('currentProduct.'.$field.'.*') <p class="{{ $err }}">{{ $message }}</p> @enderror
                                </div>
                            @endforeach

                            <fieldset>
                                <legend class="mb-1.5 text-xs font-semibold">Anwendungsarten</legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($usageTypes as $usage)
                                        <label>
                                            <input type="checkbox" class="peer sr-only" value="{{ $usage }}" wire:model="currentProduct.usage_types">
                                            <span class="flex h-10 cursor-pointer items-center gap-1 rounded-full bg-mist-50 px-4 text-[15px] font-medium text-[#1E3A8A] peer-checked:bg-navy-900 peer-checked:font-bold peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-cobalt-500 dark:bg-night-800 dark:text-chalk dark:peer-checked:bg-chalk dark:peer-checked:text-navy-900">
                                                {{ $usage }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div>
                                <label for="f-dilution" class="mb-1 block text-xs font-semibold">Hautverdünnung</label>
                                <input id="f-dilution" type="text" wire:model="currentProduct.dilution_hint" maxlength="40" placeholder="z. B. 1–2 %"
                                    class="{{ $input }} border-line text-navy-900 dark:text-chalk">
                            </div>
                        </fieldset>

                    {{-- ---------- Basisöl ---------- --}}
                    @elseif ($currentProduct->category === 'carrier_oil')
                        <fieldset wire:key="fields-carrier" class="space-y-3 rounded-[18px] bg-[#E6E0F6] p-3.5 text-[#4B2E8F] dark:bg-violet-500/20 dark:text-[#CDB7F5]">
                            <legend class="float-left mb-1 flex w-full items-center gap-2 text-xs font-bold tracking-[.08em] uppercase">
                                <span class="size-2 rounded-full bg-sky-300" aria-hidden="true"></span> Nur für Basisöle
                            </legend>

                            <div class="clear-both grid grid-cols-2 gap-2.5">
                                <div>
                                    <label for="f-latin-c" class="mb-1 block text-xs font-semibold">Botanischer Name</label>
                                    <input id="f-latin-c" type="text" wire:model="currentProduct.latin_name" maxlength="160"
                                        class="{{ $input }} border-line text-navy-900 italic dark:text-chalk">
                                </div>
                                <div>
                                    <label for="f-cert" class="mb-1 block text-xs font-semibold">Qualität</label>
                                    <input id="f-cert" type="text" wire:model="currentProduct.certification" maxlength="80"
                                        placeholder="z. B. Bio · kaltgepresst" class="{{ $input }} border-line text-navy-900 dark:text-chalk">
                                </div>
                            </div>

                            <fieldset>
                                <legend class="mb-1.5 text-xs font-semibold">Komedogenität (0–5)</legend>
                                <div class="flex justify-between gap-1">
                                    @foreach (range(0, 5) as $rating)
                                        <label>
                                            <input type="radio" class="peer sr-only" name="comedogenic_rating" value="{{ $rating }}"
                                                wire:model="currentProduct.comedogenic_rating">
                                            <span class="grid size-[42px] cursor-pointer place-items-center rounded-full bg-mist-50 text-base font-semibold text-navy-900 peer-checked:bg-navy-900 peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-cobalt-500 dark:bg-night-800 dark:text-chalk dark:peer-checked:bg-chalk dark:peer-checked:text-navy-900">
                                                {{ $rating }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('currentProduct.comedogenic_rating') <p class="{{ $err }}">{{ $message }}</p> @enderror
                            </fieldset>

                            <fieldset>
                                <legend class="mb-1.5 text-xs font-semibold">Einziehgeschwindigkeit</legend>
                                <div class="grid grid-cols-3 gap-1 rounded-[14px] bg-[#D8CEF0] p-1 dark:bg-white/10">
                                    @foreach (\App\Enums\Absorption::cases() as $absorption)
                                        <label>
                                            <input type="radio" class="peer sr-only" name="absorption" value="{{ $absorption->value }}"
                                                wire:model="currentProduct.absorption">
                                            <span class="grid h-10 cursor-pointer place-items-center rounded-[10px] text-[15px] font-semibold text-navy-900 peer-checked:bg-mist-50 peer-checked:shadow-soft peer-focus-visible:outline-2 peer-focus-visible:outline-cobalt-500 dark:text-chalk dark:peer-checked:bg-night-800">
                                                {{ $absorption->label() }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <fieldset>
                                <legend class="mb-1.5 text-xs font-semibold">Anwendungsgebiete</legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($useAreas as $area)
                                        <label>
                                            <input type="checkbox" class="peer sr-only" value="{{ $area }}" wire:model="currentProduct.use_areas">
                                            <span class="flex h-10 cursor-pointer items-center rounded-full bg-mist-50 px-4 text-[15px] font-medium text-navy-900 peer-checked:bg-navy-900 peer-checked:font-bold peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-cobalt-500 dark:bg-night-800 dark:text-chalk dark:peer-checked:bg-chalk dark:peer-checked:text-navy-900">
                                                {{ $area }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        </fieldset>
                    @endif
                </div>

                {{-- Fixed action bar --}}
                <div class="border-t border-mist-300 bg-mist-50 px-5 pt-4 pb-[max(env(safe-area-inset-bottom),1rem)] dark:border-white/10 dark:bg-night-800">
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveProduct"
                        class="h-[54px] w-full rounded-2xl bg-navy-900 text-[17px] font-bold text-white hover:bg-navy-900/90 disabled:opacity-60 dark:bg-chalk dark:text-navy-900">
                        <span wire:loading.remove wire:target="saveProduct">Speichern</span>
                        <span wire:loading wire:target="saveProduct">Speichert …</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Delete confirmation (custom dialog, no native confirm()) --}}
    <x-confirm-dialog show="confirmDelete !== null" title="Produkt löschen?" confirm-label="Löschen"
        on-confirm="$wire.deleteProduct(confirmDelete); confirmDelete = null" on-cancel="confirmDelete = null">
        Das Produkt wird aus dem Bestand entfernt.
    </x-confirm-dialog>

    {{-- Barcode scanner (fullscreen) --}}
    <x-barcode-scanner />
</div>
