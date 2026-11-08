import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import barcodeScanner from './barcode-scanner';
import themeSwitch from './theme';

Alpine.data('barcodeScanner', barcodeScanner);
Alpine.data('themeSwitch', themeSwitch);

Livewire.start();
