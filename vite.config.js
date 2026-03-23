import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import glob from "fast-glob";

export default defineConfig({
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
