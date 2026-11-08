// Installable app: service worker registration and the "App installieren" card.

let deferredPrompt = null;

export function registerPwa() {
    if ('serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }

    // Chrome/Edge/Samsung Internet: keep the install prompt for our own button.
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        window.dispatchEvent(new CustomEvent('aromacare-installable'));
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        window.dispatchEvent(new CustomEvent('aromacare-installed'));
    });
}

function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function isIos() {
    const ua = navigator.userAgent;

    // iPadOS reports itself as Mac with touch support.
    return /iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
}

export function installApp() {
    return {
        installed: isStandalone(),
        canPrompt: deferredPrompt !== null,
        ios: isIos(),

        init() {
            window.addEventListener('aromacare-installable', () => { this.canPrompt = true; });
            window.addEventListener('aromacare-installed', () => {
                this.installed = true;
                this.canPrompt = false;
            });
        },

        async install() {
            if (!deferredPrompt) {
                return;
            }

            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            deferredPrompt = null;
            this.canPrompt = false;

            if (outcome === 'accepted') {
                this.installed = true;
            }
        },
    };
}
