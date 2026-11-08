@props(['label', 'id', 'error' => null, 'hint' => null])

{{-- Labelled input with accessible error message. Extra attributes (wire:model, type, …) go to the <input>. --}}
<div>
    <label for="{{ $id }}" class="mb-1 block text-xs font-semibold text-muted dark:text-chalk/75">{{ $label }}</label>
    <input id="{{ $id }}" aria-invalid="{{ $error ? 'true' : 'false' }}"
        @if ($error || $hint) aria-describedby="{{ $id }}-help" @endif
        {{ $attributes->class([
            'h-[46px] w-full rounded-xl border bg-mist-50 px-3.5 text-base dark:bg-night-800',
            'border-violet-500' => $error,
            'border-line dark:border-white/15' => ! $error,
        ]) }}>
    @if ($error)
        <p id="{{ $id }}-help" class="mt-1 text-[13px] font-medium text-violet-700 dark:text-violet-400">{{ $error }}</p>
    @elseif ($hint)
        <p id="{{ $id }}-help" class="mt-1 text-[13px] text-muted dark:text-chalk/70">{{ $hint }}</p>
    @endif
</div>
