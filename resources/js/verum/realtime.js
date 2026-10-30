/**
 * The one way a module reaches Reverb.
 *
 * echo.js is ~90KB gzipped and only pages that listen need it, so it is
 * imported on first use; every later caller gets the same instance.
 */
export async function connect() {
    if (window.Echo) return window.Echo;

    await import('../echo.js');

    return window.Echo ?? null;
}
