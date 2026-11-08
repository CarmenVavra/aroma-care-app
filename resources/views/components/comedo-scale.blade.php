@props(['value' => 0, 'label' => null])

@php
    $tone = match (true) {
        $value <= 1 => 'bg-lilac-300',
        $value <= 3 => 'bg-sky-300',
        default => 'bg-violet-400',
    };
@endphp

<div class="rounded-(--radius-card) border border-mist-300 bg-mist-50 p-4 dark:border-white/10 dark:bg-night-800">
    <div class="mb-3 flex items-baseline justify-between">
        <h3 class="text-xs font-bold tracking-[.08em] text-violet-700 uppercase dark:text-cobalt-200">Komedogenität</h3>
        <p class="text-sm"><span class="font-display text-[22px]">{{ $value }}</span> / 5{{ $label ? ' · '.$label : '' }}</p>
    </div>
    <div class="flex gap-1.5" role="img" aria-label="Komedogenität {{ $value }} von 5{{ $label ? ', '.$label : '' }}">
        @foreach (range(1, 5) as $i)
            <span @class([
                'grid h-9 flex-1 place-items-center rounded-full text-sm font-bold',
                "$tone text-navy-900" => $i <= $value,
                'bg-mist-100 text-navy-900/60 ring-1 ring-inset ring-line dark:bg-night-900 dark:text-white/60 dark:ring-white/10' => $i > $value,
            ])>{{ $i }}</span>
        @endforeach
    </div>
    <p class="mt-2 text-xs text-muted dark:text-chalk/70">0 = verstopft keine Poren · 5 = stark</p>
</div>
