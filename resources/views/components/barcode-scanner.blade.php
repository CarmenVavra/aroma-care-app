{{--
    Fullscreen barcode scanner (Alpine component `barcodeScanner`, see resources/js/barcode-scanner.js).
    Must be placed inside a Livewire component that provides handleScannedBarcode(string $code).
    Opened via the window event `open-scanner` (detail: { mode: 'scan' | 'manual' }).
    wire:ignore: Livewire must not morph the <video> injected by html5-qrcode.
--}}
<div wire:ignore x-data="barcodeScanner({ elementId: 'barcode-scanner-region' })"
    @open-scanner.window="open($event.detail?.mode ?? 'scan')" @keydown.escape.window="isOpen && close()">
    <div x-cloak x-show="isOpen" x-trap.noscroll="isOpen" role="dialog" aria-modal="true" aria-labelledby="scanner-title"
        class="fixed inset-0 z-[60] overflow-hidden bg-night-900 text-white">

        {{-- Camera feed --}}
        <div id="barcode-scanner-region" class="absolute inset-0" x-show="mode === 'scan'"></div>

        {{-- Viewfinder with darkened surroundings, corner brackets and animated scan line --}}
        <div x-show="mode === 'scan' && (status === 'scanning' || status === 'starting')" aria-hidden="true"
            class="pointer-events-none absolute top-1/2 left-1/2 h-[170px] w-[280px] -translate-x-1/2 -translate-y-[60%] rounded-[18px] shadow-[0_0_0_200vmax_rgba(8,12,28,.68)]">
            <span class="absolute -top-1 -left-1 size-[38px] rounded-tl-[18px] border-t-4 border-l-4 border-sky-300"></span>
            <span class="absolute -top-1 -right-1 size-[38px] rounded-tr-[18px] border-t-4 border-r-4 border-sky-300"></span>
            <span class="absolute -bottom-1 -left-1 size-[38px] rounded-bl-[18px] border-b-4 border-l-4 border-sky-300"></span>
            <span class="absolute -right-1 -bottom-1 size-[38px] rounded-br-[18px] border-r-4 border-b-4 border-sky-300"></span>
            <span class="animate-scanline absolute inset-x-3 h-0.5 bg-sky-300 shadow-[0_0_12px_3px_rgba(132,206,235,.7)]"></span>
        </div>

        <div class="relative flex h-full flex-col px-5 pt-[max(env(safe-area-inset-top),1.25rem)] pb-[max(env(safe-area-inset-bottom),1.5rem)]">
            {{-- Top bar --}}
            <div class="flex items-center justify-between">
                <button type="button" @click="close()" aria-label="Scanner schließen"
                    class="grid size-11 place-items-center rounded-full bg-white/16 hover:bg-white/25">
                    <x-app-icon name="close" />
                </button>
                <h2 id="scanner-title" class="text-[17px] font-semibold">Barcode scannen</h2>
                <div class="flex items-center gap-2">
                    <button type="button" x-show="zoomLevels.length > 1" @click="cycleZoom()"
                        :aria-label="'Zoom ' + zoom + '-fach, tippen zum Ändern'"
                        class="grid h-11 min-w-11 place-items-center rounded-full bg-white/16 px-3 text-sm font-bold hover:bg-white/25"
                        x-text="zoom + '×'"></button>
                    <button type="button" @click="toggleTorch()" :disabled="!torchSupported" :aria-pressed="torchOn.toString()"
                        aria-label="Taschenlampe" :class="torchOn ? 'bg-sky-300 text-navy-900' : 'bg-white/16'"
                        class="grid size-11 place-items-center rounded-full disabled:opacity-40">
                        <x-app-icon name="torch" />
                    </button>
                </div>
            </div>

            {{-- Hint below the viewfinder --}}
            <div class="pointer-events-none absolute inset-x-0 top-[calc(50%+90px)] text-center"
                x-show="mode === 'scan' && (status === 'scanning' || status === 'starting')">
                <p class="text-base font-medium">Barcode im Rahmen ausrichten</p>
                <p class="mt-1 text-sm text-white/70">EAN · UPC · Code 128 · DataMatrix · QR</p>
                <p class="mt-1 text-sm text-white/70" x-show="zoomLevels.length > 1">Kleiner Code? Etwas Abstand halten und zoomen.</p>
            </div>

            <div class="flex-1" aria-hidden="true"></div>

            <p class="mx-auto mb-4 max-w-sm rounded-2xl px-4 py-3 text-center text-sm" role="status" aria-live="polite"
                x-show="statusMessage" x-text="statusMessage"
                :class="hasProblem ? 'bg-violet-500/30 text-white' : 'bg-white/10 text-white/85'"></p>

            {{-- Manual entry --}}
            <form x-show="mode === 'manual'" @submit.prevent="submitManual()" class="mx-auto mt-auto mb-6 w-full max-w-sm">
                <label for="manual-barcode" class="mb-2 block text-sm font-semibold text-white/85">Barcode eingeben</label>
                <input id="manual-barcode" x-ref="manualInput" x-model="manualCode" type="text" inputmode="numeric"
                    autocomplete="off" maxlength="512" placeholder="z. B. 4260123456788"
                    class="h-12 w-full rounded-xl border border-white/25 bg-white/10 px-4 text-base text-white placeholder:text-white/50">
                <button type="submit" :disabled="manualCode.trim() === ''"
                    class="mt-3 h-[54px] w-full rounded-2xl bg-sky-300 font-bold text-navy-900 disabled:opacity-50">
                    Suchen
                </button>
            </form>

            {{-- Photo upload fallback (desktop, no camera, permission denied) --}}
            <label x-show="hasProblem || mode === 'manual'"
                class="mx-auto mb-4 flex h-11 cursor-pointer items-center gap-2 rounded-full bg-white/10 px-5 text-sm font-semibold hover:bg-white/20 focus-within:outline-2 focus-within:outline-cobalt-500">
                <x-app-icon name="camera" class="size-4" />
                Foto mit Barcode hochladen
                <input type="file" accept="image/*" capture="environment" class="sr-only" @change="scanPhoto($event)">
            </label>

            {{-- Mode switch --}}
            <div class="mx-auto flex rounded-full bg-white/10 p-1" role="radiogroup" aria-label="Eingabemodus">
                <button type="button" role="radio" :aria-checked="(mode === 'scan').toString()" @click="setMode('scan')"
                    :class="mode === 'scan' ? 'bg-sky-300 text-navy-900' : 'text-white'"
                    class="h-10 w-[100px] rounded-full text-[15px] font-bold">Scannen</button>
                <button type="button" role="radio" :aria-checked="(mode === 'manual').toString()" @click="setMode('manual')"
                    :class="mode === 'manual' ? 'bg-sky-300 text-navy-900' : 'text-white'"
                    class="h-10 w-[100px] rounded-full text-[15px] font-bold">Manuell</button>
            </div>
        </div>
    </div>
</div>
