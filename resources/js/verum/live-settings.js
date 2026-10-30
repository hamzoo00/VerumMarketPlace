import { $$, onceGlobal } from './dom.js';
import { connect } from './realtime.js';

/**
 * Seller pages that read an admin's switch, kept in step with it.
 *
 * A region marked data-settings-section="listings" shows something
 * Settings decides — the listing form's "Submit for approval" or
 * "Publish", the inventory's "Request feature". When an admin saves that
 * section, PlatformSettingsChanged names it on the sellers' channel and
 * this asks the page to re-read itself (live-regions.js, live-seller.js).
 * The same shape as data-staff-area on admin pages: the server says what
 * changed, Blade says what it now reads.
 */

function watching(sections) {
    return $$('[data-settings-section]').some((region) => sections.includes(region.dataset.settingsSection));
}

export function initLiveSettings() {
    // Subscribe from the first page that needs it; later pages are asked
    // at the moment a change arrives, so navigating away needs no unbinding.
    if ($$('[data-settings-section]').length === 0) return;

    onceGlobal('live-settings', async () => {
        const echo = await connect();
        if (!echo) return;

        echo.private('platform.sellers').listen('.settings.changed', ({ sections = [] } = {}) => {
            if (watching(sections)) {
                document.dispatchEvent(new CustomEvent('verum:live-refresh', { detail: { source: 'settings' } }));
            }
        });
    });
}
