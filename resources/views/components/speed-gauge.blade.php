@props(['absorption'])

{{-- Half-circle gauge for the absorption speed (slow -60°, medium 0°, fast +60°). --}}
<div class="flex items-center gap-5 rounded-(--radius-card) border border-mist-300 bg-mist-50 p-4 dark:border-white/10 dark:bg-night-800">
    <div class="relative h-[70px] w-32 shrink-0 overflow-hidden" role="img"
        aria-label="Einziehgeschwindigkeit: {{ $absorption->label() }}">
        <div
            class="absolute top-0 size-32 rounded-full bg-[conic-gradient(from_270deg,var(--color-lilac-300)_0deg_60deg,var(--color-azure-400)_60deg_120deg,var(--color-cobalt-700)_120deg_180deg,transparent_180deg)]">
        </div>
        <div class="absolute top-[18px] left-[18px] size-[92px] rounded-full bg-mist-50 dark:bg-night-800"></div>
        <div class="absolute top-[14px] left-[63px] h-[50px] w-0.5 origin-bottom rounded bg-navy-900 dark:bg-mist-100"
            style="transform: rotate({{ $absorption->gaugeAngle() }}deg)"></div>
        <div class="absolute top-[57px] left-[57px] size-3.5 rounded-full bg-navy-900 dark:bg-mist-100"></div>
    </div>
    <div>
        <h3 class="text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">Einziehgeschwindigkeit</h3>
        <p class="font-display text-[26px] leading-tight">{{ $absorption->label() }}</p>
        <p class="text-xs text-muted dark:text-chalk/70">Langsam · Mittel · Schnell</p>
    </div>
</div>
