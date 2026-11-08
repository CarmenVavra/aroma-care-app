@props(['show', 'title', 'confirmLabel' => 'Bestätigen', 'onConfirm', 'onCancel'])

{{--
    Custom confirm dialog (no native confirm()).
    Usage: <x-confirm-dialog show="confirmDelete !== null" on-confirm="..." on-cancel="confirmDelete = null" title="…">Text</x-confirm-dialog>
--}}
<div x-cloak x-show="{{ $show }}" x-transition.opacity class="fixed inset-0 z-[80] grid place-items-center bg-night-900/60 p-4"
    @keydown.escape.window="if ({{ $show }}) { {{ $onCancel }} }">
    <div x-show="{{ $show }}" x-trap="{{ $show }}" x-scroll-lock="{{ $show }}" x-transition.scale.95 role="alertdialog" aria-modal="true"
        aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-text"
        class="w-full max-w-sm rounded-(--radius-card) bg-mist-50 p-6 shadow-soft dark:bg-night-800"
        @click.outside="{{ $onCancel }}">
        <h2 id="confirm-dialog-title" class="font-display text-[22px]">{{ $title }}</h2>
        <div id="confirm-dialog-text" class="mt-2 text-sm text-muted dark:text-chalk/75">{{ $slot }}</div>
        <div class="mt-6 flex gap-3">
            <button type="button" @click="{{ $onCancel }}"
                class="h-12 flex-1 rounded-2xl border-[1.5px] border-navy-900 font-semibold dark:border-chalk">Abbrechen</button>
            <button type="button" @click="{{ $onConfirm }}"
                class="h-12 flex-1 rounded-2xl bg-violet-700 font-semibold text-white hover:bg-violet-700/90">{{ $confirmLabel }}</button>
        </div>
    </div>
</div>
