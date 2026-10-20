import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/landing.css',
                'resources/css/public-schedule.css',
                'resources/js/landing.js',
                'resources/js/public-event-results.js',
            ],
            refresh: true,
        }),
    ],
});
