// Page scroll lock for dialogs, counted instead of saved/restored.
//
// Alpine's x-trap.noscroll remembers the previous overflow value and restores
// it on release. With overlapping dialogs (scanner closes after the detail
// sheet opened) the second lock remembers "hidden" and restores it later, so
// the page stays locked. Here every open dialog holds one lock; the page
// scrolls again as soon as the last one is released, in any order.
//
// Usage: <div x-scroll-lock="isOpen"> (default: locked while the element exists).

let locks = 0;

function apply() {
    document.documentElement.classList.toggle('overflow-hidden', locks > 0);
}

export function lockScroll() {
    locks++;
    apply();
}

export function unlockScroll() {
    locks = Math.max(0, locks - 1);
    apply();
}

export default function scrollLockDirective(Alpine) {
    Alpine.directive('scroll-lock', (el, { expression }, { effect, evaluateLater, cleanup }) => {
        const evaluate = evaluateLater(expression || 'true');
        let locked = false;

        const set = (value) => {
            if (value && !locked) {
                locked = true;
                lockScroll();
            } else if (!value && locked) {
                locked = false;
                unlockScroll();
            }
        };

        effect(() => evaluate((value) => set(Boolean(value))));

        // Element removed (e.g. Livewire closed the modal): always release.
        cleanup(() => set(false));
    });
}
