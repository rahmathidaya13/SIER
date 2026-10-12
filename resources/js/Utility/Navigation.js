import { router } from "@inertiajs/vue3";
import { startPageLoading } from "../app-loading";

export function navigateWithLoading(url, options = {}) {
    let stopLoading = null;

    router.visit(url, {
        ...options,

        onStart: (visit) => {
            stopLoading = startPageLoading(250);
            options.onStart?.(visit);
        },

        onCancel: (visit) => {
            stopLoading?.();
            options.onCancel?.(visit);
        },

        onFinish: (visit) => {
            stopLoading?.();
            options.onFinish?.(visit);
        },
    });
}
