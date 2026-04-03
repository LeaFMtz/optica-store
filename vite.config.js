/* vite.config.js */
import { defineConfig } from "vite";
import laravel, { refreshPaths } from "laravel-vite-plugin";
import basicSsl from '@vitejs/plugin-basic-ssl';

export default defineConfig({
    plugins: [
        basicSsl(),
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
    },
});
