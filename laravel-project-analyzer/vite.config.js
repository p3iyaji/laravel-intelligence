import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            input: ['resources/assets/js/app.js'],
            publicDirectory: 'public',
            buildDirectory: 'build',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/assets/js',
        },
    },
});
