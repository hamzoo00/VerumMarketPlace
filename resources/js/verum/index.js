import { initBranding } from './branding.js';
import { initCart } from './cart.js';
import { initChatComposer } from './chat-compose.js';
import { initChatLive } from './live-chat.js';
import { initCategories } from './categories.js';
import { initCategoryPicker } from './category-picker.js';
import { onceGlobal } from './dom.js';
import { initCountdowns } from './countdown.js';
import { initFilters } from './filters.js';
import { initGalleries } from './gallery.js';
import { initHeader } from './header.js';
import { initLayers } from './layers.js';
import { initListingLive } from './live-listing.js';
import { initOfferLive } from './live-offers.js';
import { initLive } from './live.js';
import { initSellerLive } from './live-seller.js';
import { initMarquees } from './marquee.js';
import { initMenus } from './menu.js';
import { initPhoneFields } from './phone.js';
import { initOfferForms } from './offers.js';
import { initPricing } from './pricing.js';
import { initReveal } from './reveal.js';
import { initScroll } from './scroll.js';
import { initSearch } from './search.js';
import { initShipping } from './shipping.js';
import { initSpecialties } from './specialties.js';
import { initToasts } from './toast.js';
import { initUploads } from './upload.js';
import { initValidation } from './validate.js';
import { initWishlist } from './wishlist.js';

/**
 * Front-end entry point.
 *
 * Every module is defensive about missing markup, so the same bootstrap runs
 * on every page without needing per-page bundles.
 */
export function boot() {
    initToasts();
    initHeader();
    initLayers();
    initCategories();
    initSearch();
    initWishlist();
    initCart();
    initCountdowns();
    initGalleries();
    initLive();
    initSellerLive();
    initListingLive();
    initOfferLive();
    initChatLive();
    initChatComposer();
    initOfferForms();
    initFilters();
    initReveal();
    initMarquees();
    initScroll();
    initValidation();
    initPhoneFields();
    initSpecialties();
    initMenus();
    initCategoryPicker();
    initUploads();
    initBranding();
    initPricing();
    initShipping();

    armLivewireRehydration();
}

/**
 * Livewire replaces parts of the DOM on every round trip, and anything it
 * swaps in was never seen by the observers set up at boot. Without this a
 * re-rendered card keeps [data-reveal]'s starting transform and sits
 * visibly offset from its neighbours.
 */
function armLivewireRehydration() {
    onceGlobal('livewire-rehydrate', () => {
        document.addEventListener('livewire:navigated', () => initReveal());

        if (!window.Livewire?.hook) return;

        window.Livewire.hook('morphed', ({ el }) => {
            initReveal(el);
            initGalleries();
            initCountdowns();
        });
    });
}
