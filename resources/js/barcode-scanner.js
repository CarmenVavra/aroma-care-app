/**
 * Alpine.js component for the fullscreen barcode scanner.
 *
 * - Uses html5-qrcode (ZXing) and, where available, the native BarcodeDetector API.
 * - The library is lazy-loaded the first time the scanner opens.
 * - A detected code is passed to Livewire: $wire.handleScannedBarcode(code).
 *
 * Status: idle | starting | scanning | processing | denied | nocamera | unsupported | error | notfound
 */
const SCANNING = 2;
const PAUSED = 3;

let libraryPromise = null;

function loadLibrary() {
    libraryPromise ??= import('html5-qrcode');

    return libraryPromise;
}

export default function barcodeScanner({ elementId = 'barcode-scanner-region' } = {}) {
    // Kept outside of Alpine's reactive state on purpose: Alpine would wrap the
    // library instance in a Proxy, which breaks its internal camera handling.
    let scanner = null;

    async function getScanner() {
        const { Html5Qrcode, Html5QrcodeSupportedFormats: F } = await loadLibrary();

        scanner ??= new Html5Qrcode(elementId, {
            verbose: false,
            formatsToSupport: [F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.CODE_128, F.QR_CODE],
            experimentalFeatures: { useBarCodeDetectorIfSupported: true },
        });

        return scanner;
    }

    return {
        isOpen: false,
        mode: 'scan',
        status: 'idle',
        manualCode: '',
        torchSupported: false,
        torchOn: false,

        get statusMessage() {
            return {
                starting: 'Kamera wird gestartet …',
                processing: 'Barcode erkannt – wird geprüft …',
                denied: 'Kein Zugriff auf die Kamera. Bitte erlaube den Kamerazugriff in den Browser-Einstellungen oder gib den Code manuell ein.',
                nocamera: 'Es wurde keine Kamera gefunden. Lade ein Foto hoch oder gib den Code manuell ein.',
                unsupported: 'Die Kamera ist nur über HTTPS verfügbar. Lade ein Foto hoch oder gib den Code manuell ein.',
                error: 'Die Kamera konnte nicht gestartet werden. Bitte versuche es erneut.',
                notfound: 'Im Foto wurde kein Barcode erkannt. Bitte versuche es mit einem schärferen Bild.',
            }[this.status] ?? '';
        },

        get hasProblem() {
            return ['denied', 'nocamera', 'unsupported', 'error', 'notfound'].includes(this.status);
        },

        async open(mode = 'scan') {
            this.isOpen = true;
            this.manualCode = '';
            document.documentElement.classList.add('overflow-hidden');

            await this.$nextTick();
            await this.setMode(mode);
        },

        async close() {
            this.isOpen = false;
            this.status = 'idle';
            document.documentElement.classList.remove('overflow-hidden');
            await this.stopCamera();
        },

        async setMode(mode) {
            this.mode = mode;

            if (mode === 'manual') {
                await this.stopCamera();
                this.status = 'idle';
                this.$nextTick(() => this.$refs.manualInput?.focus());

                return;
            }

            await this.startCamera();
        },

        async startCamera() {
            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                this.status = 'unsupported';

                return;
            }

            this.status = 'starting';

            try {
                const instance = await getScanner();

                if (instance.getState() === SCANNING || instance.getState() === PAUSED) {
                    this.status = 'scanning';

                    return;
                }

                await instance.start(
                    { facingMode: 'environment' },
                    { fps: 10 },
                    (decodedText) => this.onDetected(decodedText),
                    () => {}, // "no code in this frame" – ignore
                );

                // Modal closed while the camera was starting up.
                if (!this.isOpen || this.mode !== 'scan') {
                    await this.stopCamera();

                    return;
                }

                this.status = 'scanning';
                this.detectTorch(instance);
            } catch (error) {
                const message = `${error?.name ?? ''} ${error?.message ?? error}`;

                if (/NotAllowed|Permission/i.test(message)) {
                    this.status = 'denied';
                } else if (/NotFound|OverconstrainedError|Requested device not found/i.test(message)) {
                    this.status = 'nocamera';
                } else {
                    this.status = 'error';
                }
            }
        },

        async stopCamera() {
            this.torchOn = false;
            this.torchSupported = false;

            if (!scanner) {
                return;
            }

            try {
                const state = scanner.getState();

                if (state === SCANNING || state === PAUSED) {
                    await scanner.stop();
                }

                scanner.clear();
            } catch {
                // Camera already stopped – nothing to do.
            }
        },

        detectTorch(instance) {
            try {
                this.torchSupported = Boolean(instance.getRunningTrackCapabilities()?.torch);
            } catch {
                this.torchSupported = false;
            }
        },

        async toggleTorch() {
            if (!scanner || !this.torchSupported) {
                return;
            }

            try {
                await scanner.applyVideoConstraints({ advanced: [{ torch: !this.torchOn }] });
                this.torchOn = !this.torchOn;
            } catch {
                this.torchSupported = false;
            }
        },

        async onDetected(code) {
            if (this.status === 'processing') {
                return; // ignore duplicate frames
            }

            this.status = 'processing';
            navigator.vibrate?.(60);

            await this.stopCamera();
            await this.submit(code);
        },

        submitManual() {
            this.submit(this.manualCode);
        },

        async scanPhoto(event) {
            const file = event.target.files?.[0];
            event.target.value = '';

            if (!file) {
                return;
            }

            await this.stopCamera();
            this.status = 'processing';

            try {
                const instance = await getScanner();
                const code = await instance.scanFile(file, false);
                await this.submit(code);
            } catch {
                this.status = 'notfound';
            }
        },

        async submit(code) {
            const value = String(code ?? '').trim();

            if (value === '') {
                return;
            }

            await this.close();
            await this.$wire.handleScannedBarcode(value);
        },

        destroy() {
            document.documentElement.classList.remove('overflow-hidden');
            this.stopCamera();
        },
    };
}
