/* vite.config.js */
import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  return {
    plugins: [
      laravel({
        input: ["resources/css/app.css", "resources/js/app.js"],
        refresh: true,
      }),
      vue(),
    ],
    server: {
      host: "0.0.0.0",
      port: 5173,
      hmr: {
        host: env.VITE_HMR_HOST || "localhost",
        protocol: env.VITE_HMR_PROTOCOL || "ws",
        clientPort: env.VITE_HMR_CLIENT_PORT || 5173,
      },
      watch: {
        ignored: ["**/vendor/**", "**/storage/**", "**/.git/**"],
      },
    },
  };
});
