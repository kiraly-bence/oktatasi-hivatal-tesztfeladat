import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    root: 'resources',
    define: {
        'process.env.NODE_ENV': '"production"',
    },
    build: {
        outDir: '../public',
        lib: {
            entry: 'js/app.js',
            name: 'app',
            formats: ['es'],
            fileName: () => 'js/app.js',
            cssFileName: 'css/app',
        },
    },
    plugins: [
        vue(),
    ],
});