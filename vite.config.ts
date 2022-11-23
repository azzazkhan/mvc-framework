import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'public/dist',
        copyPublicDir: false,
        assetsDir: '',
        sourcemap: true,
        manifest: true,
        rollupOptions: {
            input: ['resources/css/tailwind.css', 'resources/ts/main.ts']
        }
    },
    css: {
        devSourcemap: true
    },
    server: {
        hmr: {
            host: 'localhost'
        }
    },
    appType: 'custom'
});
