import { ref } from "vue";

// State global (di luar fungsi agar reaktif di seluruh halaman/komponen)
const toasts = ref([]);
const timers = new Map(); // Map untuk menyimpan timer untuk setiap toast
export function useToast() {
    /**
     * Tambahkan toast baru ke antrean
     * @param {String} message - Pesan yang ditampilkan
     * @param {String} type - 'success' | 'danger' | 'warning' | 'primary'
     * @param {Number} duration - Waktu tampil dalam milidetik (default 4 detik)
     */
    const add = (message, type = "primary", duration = 4000) => {
        if (!message) return; // Jika pesan kosong, tidak ditampilkan toast

        const id = Date.now() + Math.random(); // Membuat ID unik untuk toast
        toasts.value.push({ id, message, type, duration });

        // Jika duration > 0, jalankan waktu hitung mundur otomatis
        // Jika duration === 0, lewati blok ini (hanya bisa ditutup lewat tombol close)
        if (duration > 0) {
            const timer = setTimeout(() => {
                remove(id);
            }, duration);

            timers.set(id, timer);
        }
    };

    const remove = (id) => {
        // Hentikan timer jika user menutup lebih cepat lewat tombol X
        if (timers.has(id)) {
            clearTimeout(timers.get(id));
            timers.delete(id);
        }

        toasts.value = toasts.value.filter((t) => t.id !== id);
    };

    return {
        toasts,
        add,
        remove,
        success: (msg, duration = 4000) => add(msg, "success", duration),
        error: (msg, duration = 4000) => add(msg, "danger", duration),
        warning: (msg, duration = 4000) => add(msg, "warning", duration),
        info: (msg, duration = 4000) => add(msg, "primary", duration),
    };
}
