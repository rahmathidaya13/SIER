import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import inject from "@rollup/plugin-inject";
import path from "path";
export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),

        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),

        // Inject jQuery
        inject({
            $: "jquery",
            jQuery: "jquery",
            "window.jQuery": "jquery",
            "window.$": "jquery",
            include: ["resources/js/**/*.js", "resources/js/**/*.vue"],
            // exclude: ["**/*.css"],
        }),
    ],

    server: {
        hmr: {
            overlay: false, // matikan overlay error
        },
        watch: {
            usePolling: false,
            ignored: ["**/vendor/**", "**/storage/**", "**/node_modules/**"],
        },
    },

    resolve: {
        alias: {
            "@": path.resolve(__dirname, "resources/js"),
        },
    },

    build: {
        sourcemap: false,
    },
});
