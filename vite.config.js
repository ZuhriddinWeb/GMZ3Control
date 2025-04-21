import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
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
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    // server: {
    //     cors: {
    //         origin: ['http://192.168.14.82:8001'], // Bu manzildan kirishga ruxsat bering
    //         methods: ['GET', 'POST'], // Ruxsat berilgan metodlar
    //     },
    // },
    
});
