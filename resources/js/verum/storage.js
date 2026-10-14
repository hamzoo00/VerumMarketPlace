/**
 * Namespaced localStorage wrapper.
 *
 * Browser storage can throw (private mode, blocked site data), so every access
 * is guarded and falls back to a default rather than breaking the page.
 */

const PREFIX = 'verum.';

export const store = {
    get(key, fallback = null) {
        try {
            const raw = localStorage.getItem(PREFIX + key);
            return raw === null ? fallback : JSON.parse(raw);
        } catch {
            return fallback;
        }
    },

    set(key, value) {
        try {
            localStorage.setItem(PREFIX + key, JSON.stringify(value));
            return true;
        } catch {
            return false;
        }
    },

    remove(key) {
        try {
            localStorage.removeItem(PREFIX + key);
        } catch {
            /* ignore */
        }
    },
};
