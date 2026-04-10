import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import glob from "fast-glob";

const devServerHost = process.env.VITE_DEV_SERVER_HOST || "127.0.0.1";
const devServerPort = Number(process.env.VITE_DEV_SERVER_PORT || 5173);
const devServerHttps = process.env.VITE_DEV_SERVER_HTTPS === "true";
const devServerProtocol = devServerHttps ? "https" : "http";
const devServerOrigin =
    process.env.VITE_DEV_SERVER_ORIGIN ||
    `${devServerProtocol}://${devServerHost}:${devServerPort}`;

export default defineConfig({
    server: {
        host: "0.0.0.0",
        port: devServerPort,
        strictPort: true,
        cors: true,
        origin: devServerOrigin,
        hmr: {
            host: devServerHost,
            port: devServerPort,
            protocol: devServerHttps ? "wss" : "ws",
        },
    },
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                ...glob.sync("resources/js/admin/**/*.js"),
                ...glob.sync("resources/js/manager/**/*.js"),
                ...glob.sync("resources/js/super-admin/**/*.js"),
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
