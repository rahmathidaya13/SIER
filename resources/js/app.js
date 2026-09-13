import "./bootstrap";
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "bootstrap/dist/js/bootstrap.min.js";
import "@popperjs/core/dist/umd/popper.min.js";
import { createInertiaApp } from "@inertiajs/vue3";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

import GlobalComponent from "./GlobalComponent";
import select2 from "select2";
const appName = import.meta.env.VITE_APP_NAME || "";
select2();

// setup to app
createInertiaApp({
    title: (title) => `${appName} - ${title}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue", { eager: true })
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(GlobalComponent);
        app.use(plugin);
        app.use(ZiggyVue);
        app.mount(el);
        return app;
    },
    progress: {
        color: "#4B5563",
    },
});
