import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    // --- AGREGA ESTA SECCIÓN ---
    server: {
        watch: {
            ignored: ['**/vendor/**', '**/node_modules/**', '**/storage/**'],
        },
    },
});
