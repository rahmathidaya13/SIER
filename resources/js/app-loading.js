import { ref } from "vue";

export const isLoading = ref(false);

let timer = null;
let activeId = 0;

export function startPageLoading(delay = 250) {
    const id = ++activeId;
    let stopped = false;

    clearTimeout(timer);
    isLoading.value = false;

    timer = setTimeout(() => {
        if (id === activeId) {
            isLoading.value = true;
        }
    }, delay);

    return function stopPageLoading() {
        if (stopped) return;
        stopped = true;

        // Jangan biarkan visit lama menghentikan loading visit baru.
        if (id !== activeId) return;

        clearTimeout(timer);
        isLoading.value = false;
        activeId++;
    };
}
