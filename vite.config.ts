import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            // Vercel build containers often don't have `php` available, but our Wayfinder types
            // are already checked into the repo. Skip regeneration on Vercel.
            routes: process.env.VERCEL !== '1' && process.env.VERCEL !== 'true',
            actions: process.env.VERCEL !== '1' && process.env.VERCEL !== 'true',
            formVariants:
                process.env.VERCEL !== '1' && process.env.VERCEL !== 'true',
        }),
    ],
});
