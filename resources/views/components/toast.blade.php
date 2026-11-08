{{-- Global toast; listens for Livewire `notify` events: $this->dispatch('notify', message: '…', type: 'error') --}}
<div x-data="{ items: [], push(detail) { const id = Date.now() + Math.random(); this.items.push({ id, ...detail }); setTimeout(() => this.items = this.items.filter(i => i.id !== id), 4000); } }"
    @notify.window="push($event.detail)" role="status" aria-live="polite"
    class="pointer-events-none fixed inset-x-0 bottom-28 z-[70] flex flex-col items-center gap-2 px-4 lg:bottom-6 lg:items-end lg:px-8">
    <template x-for="item in items" :key="item.id">
        <div x-transition.opacity
            class="pointer-events-auto max-w-sm rounded-2xl px-4 py-3 text-sm font-semibold shadow-soft"
            :class="item.type === 'error' ? 'bg-violet-700 text-white' : 'bg-navy-900 text-white dark:bg-chalk dark:text-navy-900'"
            x-text="item.message"></div>
    </template>
</div>
