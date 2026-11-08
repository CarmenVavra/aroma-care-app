{{-- Segmented control: Hell / Dunkel / Automatisch (device setting). --}}
@php
    $options = ['light' => 'Hell', 'dark' => 'Dunkel', 'system' => 'Automatisch'];
@endphp

<div x-data="themeSwitch" role="radiogroup" aria-label="Darstellung"
    {{ $attributes->class('grid grid-cols-3 gap-1 rounded-2xl bg-segment p-1 dark:bg-white/5') }}
    @keydown.arrow-right.prevent="set({{ Js::from(array_keys($options)) }}[({{ Js::from(array_keys($options)) }}.indexOf(mode) + 1) % 3]); $nextTick(() => $el.querySelector('[aria-checked=true]')?.focus())"
    @keydown.arrow-left.prevent="set({{ Js::from(array_keys($options)) }}[({{ Js::from(array_keys($options)) }}.indexOf(mode) + 2) % 3]); $nextTick(() => $el.querySelector('[aria-checked=true]')?.focus())">
    @foreach ($options as $value => $label)
        <button type="button" role="radio" @click="set('{{ $value }}')"
            :aria-checked="(mode === '{{ $value }}').toString()" :tabindex="mode === '{{ $value }}' ? 0 : -1"
            class="h-11 rounded-xl text-sm font-semibold text-muted transition-colors dark:text-chalk/70"
            :class="mode === '{{ $value }}' && 'bg-mist-50 !text-navy-900 shadow-soft dark:bg-night-800 dark:!text-chalk'">
            {{ $label }}
        </button>
    @endforeach
</div>
