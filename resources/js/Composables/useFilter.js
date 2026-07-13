import { reactive, watch, ref } from "vue";
import { router } from "@inertiajs/vue3";
import debounce from "lodash/debounce";
/**
 * Reusable Filter Composable
 */
export function useFilter({
    routeName,
    initialFilters = {},
    watchFields = [],
    only = [],
    debounceTime = 500,
}) {
    const isLoading = ref(false);

    const filters = reactive({
        ...initialFilters,
    });

    /**
     * Bersihkan filter kosong
     */
    const cleanFilters = () => {
        return Object.fromEntries(
            Object.entries(filters).filter(([key, value]) => {
                return (
                    value !== null &&
                    value !== undefined &&
                    value !== "" &&
                    value !== 0 &&
                    value !== "undefined" &&
                    value !== "null" &&
                    value !== "0"
                );
            })
        );
    };

    /**
     * Jalankan request filter
     */
    const submit = debounce(() => {
        isLoading.value = true;

        router.get(route(routeName), cleanFilters(), {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only,
            onFinish: () => {
                isLoading.value = false;
            },
        });
    }, debounceTime);

    /**
     * Reset filter
     */
    const reset = () => {
        Object.keys(filters).forEach((key) => {
            filters[key] = null;
        });

        submit();
    };

    /**
     * Watch otomatis
     */
    watch(
        () => watchFields.map((field) => filters[field]),
        () => submit(),
        { deep: true }
    );

    return {
        filters,
        isLoading,
        submit,
        reset,
        cleanFilters,
        watchFields,
    };
}
