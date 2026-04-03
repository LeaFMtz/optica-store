/* vite.config.js */
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import fs from "fs";

export default defineConfig({
  plugins: [
    laravel({
      input: ["resources/css/app.css", "resources/js/app.js"],
      refresh: true,
    }),
  ],
  server: {
    host: "0.0.0.0",
    port: 5173,
    https: {
      key: fs.readFileSync("./docker/ssl/key.pem"),
      cert: fs.readFileSync("./docker/ssl/cert.pem"),
    },
    hmr: {
      host: "ecomm.localhost",
      protocol: "wss",
    },
  },
});
