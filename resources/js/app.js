import "./bootstrap";
import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "bootstrap/dist/js/bootstrap.min.js";
import "@popperjs/core/dist/umd/popper.min.js";
import { createInertiaApp, router } from "@inertiajs/vue3";
import { createApp, Fragment, h, ref } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import GlobalComponent from "./GlobalComponent";
import select2 from "select2";
const appName = import.meta.env.VITE_APP_NAME || "";
select2();
const pages = import.meta.glob("./Pages/**/*.vue"); // Mengimpor semua komponen halaman dari direktori Pages

// setup to app
createInertiaApp({
    title: (title) => `${appName} - ${title}`, // Setel judul halaman dengan format "Nama Aplikasi - Judul Halaman"
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, pages), // Resolve komponen halaman berdasarkan nama file di direktori Pages
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () =>
                h(Fragment, null, [
                    h(App, props), // Render komponen utama Inertia
                ]),
        });
        app.use(GlobalComponent); // Gunakan plugin GlobalComponent
        app.use(plugin); // Gunakan plugin Inertia
        app.use(ZiggyVue); // Gunakan plugin ZiggyVue
        app.mount(el); // Mount aplikasi ke elemen DOM dengan ID "app"
        return app;
    },
    progress: {
        color: "#f7baba",
        delay: 250,
        showSpinner: false,
    },
    defaults: {
        form: {
            recentlySuccessfulDuration: 3000, // Durasi dalam milidetik untuk menampilkan status "recently successful" pada form Inertia (3 detik)
        },

        prefetch: {
            cacheFor: "1m", // Durasi cache untuk prefetching halaman Inertia (1 menit)
            hoverDelay: 150, // Delay dalam milidetik sebelum melakukan prefetching saat hover (150ms)
        },
    },
});
