/* vite.config.js */
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import fs from "fs";

export default defineConfig(({}) => {
  const keyPath = "./docker/ssl/key.pem";
  const certPath = "./docker/ssl/cert.pem";
  const hasCert = fs.existsSync(keyPath) && fs.existsSync(certPath);

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
      https: hasCert
        ? {
            key: fs.readFileSync(keyPath),
            cert: fs.readFileSync(certPath),
          }
        : false,
      hmr: {
        host: "ecomm.localhost",
        protocol: "wss",
      },
    },
  };
});
