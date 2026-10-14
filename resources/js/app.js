import { boot } from './verum/index.js';

boot();

// Livewire swaps page content without a full reload; re-arm the enhancements.
document.addEventListener('livewire:navigated', boot);
