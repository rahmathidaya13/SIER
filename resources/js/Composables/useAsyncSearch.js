import { ref, reactive } from "vue";
import axios from "axios";
import { debounce } from "lodash";

/**
 * Helper Reusable khusus untuk <multi-select>
 * @param {String} routeName - Nama route ziggy (cth: 'product.search')
 * @param {Boolean} isMultiple - true jika array ([]), false jika objek tunggal (null)
 * @param {Function} onSync - Callback otomatis saat item dipilih / dihapus
 * @param {String} text - Nama item
 * @param {Number} delay - Delay ketika mengetik (400)
 */
export function useAsyncSearch(
    routeName,
    isMultiple = false,
    onSync = null,
    text = null,
    delay = 400,
) {
    const options = ref([]);
    const loading = ref(false);
    const selected = ref(isMultiple ? [] : null);

    const search = debounce(async (query) => {
        if (!query || query.trim().length < 2) {
            options.value = [];
            return;
        }
        loading.value = true;
        try {
            const { data } = await axios.get(route(routeName), {
                params: { keyword: query.trim() },
            });
            options.value = data;
        } catch (error) {
            console.error(`Gagal memuat data ${text}:`, error);
            options.value = [];
        } finally {
            loading.value = false;
        }
    }, delay);

    // Dipanggil pada @update:model-value
    const onSelect = (val) => {
        selected.value = val;
        if (onSync) onSync(val);
    };

    // Menghapus item dari kartu di bawah field (atau dari @remove)
    const clear = (itemOrId, key = "id") => {
        if (isMultiple && Array.isArray(selected.value)) {
            const targetId =
                typeof itemOrId === "object" ? itemOrId[key] : itemOrId;
            (selected,
                (value = selected.value.filter(
                    (item) => item[key] !== targetId,
                )));
        } else {
            selected.value = null;
        }
        if (onSync) onSync(selected.value);
    };
    const reset = () => {
        options.value = [];
        loading.value = false;
        selected.value = isMultiple ? [] : null;
    };

    return reactive({
        options,
        loading,
        selected,
        search,
        clear,
        onSelect,
        reset,
    });
}
