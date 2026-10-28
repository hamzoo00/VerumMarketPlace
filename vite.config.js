import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

// Compose passes VITE_PORT in and publishes the same number, so the bind,
// the mapping and the advertised origin cannot drift apart.
const port = Number(process.env.VITE_PORT) || 15173;

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
                'resources/js/echo.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        host: '0.0.0.0',
        port,
        strictPort: true,
        // Advertise a browser-reachable origin, not the container bind address.
        origin: `http://localhost:${port}`,
        hmr: {
            host: 'localhost',
            protocol: 'ws',
        },
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
