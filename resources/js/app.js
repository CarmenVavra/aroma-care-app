import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import barcodeScanner from './barcode-scanner';

Alpine.data('barcodeScanner', barcodeScanner);

Livewire.start();
