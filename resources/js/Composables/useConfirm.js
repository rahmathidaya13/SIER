import { reactive, watch } from "vue";

const state = reactive({
    show: false,
    title: "",
    message: "",
    icon: "",
    confirmText: "Ya, Lanjutkan",
    cancelText: "Batal",
    variant: "danger",
    variantIcon: "danger",
    variantButton: "danger",
    onConfirm: null,
    onCancel: null,
    showButtonConfirm: true,

    requireCheckbox: false,
    checkboxText: "",
    isChecked: false, // Untuk menyimpan status ceklis
});
// cegah agar user tidak bisa scroll ketika alert terbuka
watch(
    () => state.show,
    (newVal) => {
        if (newVal) {
            document.body.style.overflow = "hidden";
        } else {
            document.body.style.overflow = "";
        }
    }
);
export const useConfirm = () => {
    const ask = (options) => {
        state.title = options.title || "Konfirmasi";
        state.message = options.message || "Apakah anda yakin?";
        state.messageFontSize = options.messageFontSize || "1rem";
        state.confirmText = options.confirmText || "Ya, Lanjutkan";
        state.icon = options.icon || "fas fa-exclamation-triangle";
        state.cancelText = options.cancelText || "Batal";
        state.variant = options.variant || "danger";
        state.variantIcon = options.variantIcon || "danger";
        state.variantButton = options.variantButton || "danger";
        state.show = true;
        state.showButtonConfirm = options.showButtonConfirm ?? true;

        state.requireCheckbox = options.requireCheckbox ?? false;
        state.checkboxText =
            options.checkboxText || "Saya setuju dengan syarat dan ketentuan";
        state.isChecked = false; // Reset setiap kali modal buka

        // Mengembalikan Promise agar bisa pakai async/await
        return new Promise((resolve) => {
            state.onConfirm = () => {
                state.show = false;
                resolve(true);
            };
            state.onCancel = () => {
                state.show = false;
                resolve(false);
            };
        });
    };

    return { state, ask };
};
