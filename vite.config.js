import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/sale-form.js',
                'resources/js/product-form.js',
                'resources/js/member-form.js',
                'resources/js/member-tree.js',
                'resources/js/member-profile.js',
                'resources/js/cash-form.js',
                'resources/js/login-form.js',
                'resources/js/user-form.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
