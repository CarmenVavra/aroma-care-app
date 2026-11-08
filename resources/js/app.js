import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import barcodeScanner from './barcode-scanner';
import themeSwitch from './theme';
import { installApp, registerPwa } from './pwa';
import scrollLock from './scroll-lock';

registerPwa();

Alpine.plugin(scrollLock);
Alpine.data('barcodeScanner', barcodeScanner);
Alpine.data('themeSwitch', themeSwitch);
Alpine.data('installApp', installApp);

Livewire.start();
