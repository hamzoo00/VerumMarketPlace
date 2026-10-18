import { initCart } from './cart.js';
import { initCategories } from './categories.js';
import { initCountdowns } from './countdown.js';
import { initHeader } from './header.js';
import { initLayers } from './layers.js';
import { initMarquees } from './marquee.js';
import { initMenus } from './menu.js';
import { initPhoneFields } from './phone.js';
import { initReveal } from './reveal.js';
import { initScroll } from './scroll.js';
import { initSearch } from './search.js';
import { initSpecialties } from './specialties.js';
import { initValidation } from './validate.js';
import { initWishlist } from './wishlist.js';

/**
 * Front-end entry point.
 *
 * Every module is defensive about missing markup, so the same bootstrap runs
 * on every page without needing per-page bundles.
 */
export function boot() {
    initHeader();
    initLayers();
    initCategories();
    initSearch();
    initWishlist();
    initCart();
    initCountdowns();
    initReveal();
    initMarquees();
    initScroll();
    initValidation();
    initPhoneFields();
    initSpecialties();
    initMenus();
}
