import { ref } from "vue";

export function useRequest() {
    const isLoading = ref(false);
    const error = ref(null);
    const requests = async (
        callback,
        errorMessage = "Terjadi kesalahan",
        showLoading = true
    ) => {
        if (showLoading) isLoading.value = true;
        error.value = null;
        try {
            return await callback();
        } catch (errors) {
            console.error(errorMessage, errors);
            error.value = errorMessage;
            throw errors;
        } finally {
            if (showLoading) isLoading.value = false;
        }
    };
    return { isLoading, error, requests };
}
