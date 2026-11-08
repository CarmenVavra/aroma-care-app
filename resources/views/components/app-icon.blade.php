@props(['name'])

{{-- Minimal inline icon set (Lucide-style strokes). Decorative by default. --}}
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
    focusable="false">
    @switch($name)
        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        @break

        @case('scan')
            <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2" />
            <path d="M7 8v8M10 8v8M13 8v8M17 8v8" />
        @break

        @case('close')
            <path d="M18 6 6 18M6 6l12 12" />
        @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
        @break

        @case('edit')
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
        @break

        @case('trash')
            <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" />
        @break

        @case('torch')
            <path d="M18 6c0 2-2 2-2 4v10a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V10c0-2-2-2-2-4V2h12z" />
            <path d="M6 6h12M12 12v2" />
        @break

        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
        @break

        @case('chevron-right')
            <path d="m9 18 6-6-6-6" />
        @break

        @case('chevron-left')
            <path d="m15 18-6-6 6-6" />
        @break

        @case('grid')
            <circle cx="7" cy="7" r="2.5" fill="currentColor" />
            <circle cx="17" cy="7" r="2.5" fill="currentColor" />
            <circle cx="7" cy="17" r="2.5" fill="currentColor" />
            <circle cx="17" cy="17" r="2.5" fill="currentColor" />
        @break

        @case('recipe')
            <rect x="5" y="3" width="14" height="18" rx="3" />
            <path d="M9 9h6M9 13h6" />
        @break

        @case('user')
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
        @break

        @case('drop')
            <path d="M12 2.7S5 10 5 14.5a7 7 0 0 0 14 0C19 10 12 2.7 12 2.7Z" fill="currentColor" stroke="none" />
        @break

        @case('check')
            <path d="M20 6 9 17l-5-5" />
        @break

        @case('camera')
            <path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z" />
            <circle cx="12" cy="13" r="3" />
        @break

        @case('moon')
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
        @break

        @case('alert')
            <path d="M12 9v4M12 17h.01" />
            <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
        @break
    @endswitch
</svg>
