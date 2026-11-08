@props(['status'])

{{-- Status is always shown as text, never by colour alone. --}}
<span {{ $attributes->class(['inline-flex items-center rounded-xl px-2.5 py-1 text-xs font-bold whitespace-nowrap', $status->badgeClasses()]) }}>
    {{ $status->label() }}
</span>
