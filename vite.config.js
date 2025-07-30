import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
      server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000/api', // ❌ This adds extra /api
        changeOrigin: true,
      }
    }
  }

});
