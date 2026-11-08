// Theme handling: "light", "dark" or "system" (follows the device setting).
// The choice is stored per browser in localStorage; the inline <x-theme-script>
// applies it before the first paint, this component keeps it in sync afterwards.

const STORAGE_KEY = 'aromacare-theme';
const MODES = ['light', 'dark', 'system'];
const media = window.matchMedia('(prefers-color-scheme: dark)');

function readMode() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return MODES.includes(stored) ? stored : 'system';
    } catch (e) {
        return 'system';
    }
}

function apply(mode) {
    const dark = mode === 'dark' || (mode === 'system' && media.matches);
    document.documentElement.classList.toggle('dark', dark);

    return dark;
}

export default function themeSwitch() {
    return {
        mode: readMode(),
        dark: document.documentElement.classList.contains('dark'),

        init() {
            // Several switches can be on one page (sidebar + profile): keep them in sync.
            window.addEventListener('aromacare-theme-changed', (event) => {
                this.mode = event.detail.mode;
                this.dark = event.detail.dark;
            });

            media.addEventListener('change', () => {
                if (this.mode === 'system') {
                    this.dark = apply('system');
                }
            });
        },

        set(mode) {
            if (! MODES.includes(mode)) {
                return;
            }

            try {
                if (mode === 'system') {
                    localStorage.removeItem(STORAGE_KEY);
                } else {
                    localStorage.setItem(STORAGE_KEY, mode);
                }
            } catch (e) {
                // Storage blocked (private mode): the choice still applies to this page.
            }

            const dark = apply(mode);
            window.dispatchEvent(new CustomEvent('aromacare-theme-changed', { detail: { mode, dark } }));
        },

        toggle() {
            this.set(this.dark ? 'light' : 'dark');
        },
    };
}
