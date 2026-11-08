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
            // Small bottles often carry Code 128, GS1 DataBar or DataMatrix instead of EAN.
            formatsToSupport: [
                F.EAN_13, F.EAN_8, F.UPC_A, F.UPC_E, F.UPC_EAN_EXTENSION,
                F.CODE_128, F.CODE_39, F.CODE_93, F.ITF, F.CODABAR,
                F.RSS_14, F.RSS_EXPANDED, F.QR_CODE, F.DATA_MATRIX, F.PDF_417, F.AZTEC,
            ],
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
        zoomLevels: [],
        zoom: 1,

        get statusMessage() {
            return {
                starting: 'Kamera wird gestartet …',
                processing: 'Barcode erkannt – Produktdaten werden gesucht …',
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

            await this.$nextTick();
            await this.setMode(mode);
        },

        async close() {
            this.isOpen = false;
            this.status = 'idle';
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
                    {
                        fps: 15,
                        // Higher resolution and continuous focus: needed for the tiny codes on 5–15 ml bottles.
                        videoConstraints: {
                            facingMode: 'environment',
                            width: { ideal: 1920 },
                            height: { ideal: 1080 },
                            advanced: [{ focusMode: 'continuous' }],
                        },
                    },
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
                this.detectZoom(instance);
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
            this.zoomLevels = [];
            this.zoom = 1;

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

        detectZoom(instance) {
            try {
                const caps = instance.getRunningTrackCapabilities()?.zoom;

                this.zoomLevels = caps && caps.max > 1
                    ? [1, 2, 3].filter((level) => level >= caps.min && level <= caps.max)
                    : [];
            } catch {
                this.zoomLevels = [];
            }
        },

        async cycleZoom() {
            if (!scanner || this.zoomLevels.length < 2) {
                return;
            }

            const next = this.zoomLevels[(this.zoomLevels.indexOf(this.zoom) + 1) % this.zoomLevels.length] ?? 1;

            try {
                await scanner.applyVideoConstraints({ advanced: [{ zoom: next }] });
                this.zoom = next;
            } catch {
                this.zoomLevels = [];
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

            // Keep the "searching" state visible while the server looks up product data.
            this.status = 'processing';
            await this.stopCamera();

            try {
                await this.$wire.handleScannedBarcode(value);
            } finally {
                await this.close();
            }
        },

        destroy() {
            this.stopCamera();
        },
    };
}
