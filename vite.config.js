/* vite.config.js */
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig(({}) => {
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
      watch: {
        ignored: ["**/vendor/**", "**/storage/**", "**/.git/**"],
      },
    },
  };
});
