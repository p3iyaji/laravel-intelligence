import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    // Relative base so dynamic imports resolve correctly when assets are
    // served from public/vendor/project-analyzer/build/ in host applications.
    base: './',
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
