<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { Modal } from 'bootstrap'
const props = defineProps({
    show: Boolean,
    title: String,
    subTitle: {
        type: String,
        default: ''
    },
    size: {
        type: String,
        default: '',
    },
    position: {
        type: String,
        default: '',
    },
    width: { type: String, default: '' },
    height: { type: String, default: '' },
    icon: {
        type: [String, Object, Array],
        default: null,
    },
    footer: {
        type: Boolean,
        default: true
    },
    classModalTitle: {
        type: String,
        default: ''
    },
    mobileFullscreen: {
        type: Boolean,
        default: true // Default aktif agar di HP otomatis full-screen
    }
})

const emit = defineEmits(["update:show", "opened", "closed", "save"])

const modalEl = ref(null)
let modalInstance = null
onMounted(async () => {
    modalInstance = new Modal(modalEl.value, {
        backdrop: true,
        keyboard: false,
        backdrop: 'static',
        focus: true
    })
    if (props.show) {
        await nextTick();
        modalInstance.show()
    }

    modalEl.value.addEventListener('shown.bs.modal', () => {
        emit("opened")
    })

    modalEl.value.addEventListener('hidden.bs.modal', () => {
        emit("update:show", false)
        emit("closed")
    })
})

// CLOSE HANDLER YANG BENAR
const closeModal = () => {
    document.activeElement?.blur()  // Pindahkan fokus sebelum modal menyembunyikan dirinya
    if (modalInstance) modalInstance.hide()
}
onUnmounted(() => {
    // 1. Coba hide dan dispose instance bootstrap standar
    if (modalInstance) {
        modalInstance.dispose();
        modalInstance.hide();
    }
    // 2. PEMBERSIHAN PAKSA (Force Cleanup)
    // Ini menghapus sisa-sisa elemen jika Bootstrap gagal membersihkannya
    // karena perpindahan halaman Inertia yang terlalu cepat.
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    // Hapus backdrop hitam yang tertinggal di DOM
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
})

watch(() => props.show, async (v) => {
    if (!modalInstance) return
    if (v) {
        await nextTick();
        modalInstance.show()
    } else {
        document.activeElement?.blur()  // Pindahkan fokus sebelum modal menyembunyikan dirinya
        modalInstance.hide()
    }
})
</script>
<template>
    <div class="modal custom-modal fade" tabindex="-1" ref="modalEl">
        <div :class="[
            'modal-dialog modal-dialog-scrollable modal-dialog-centered',
            position,
            size,
            mobileFullscreen ? 'modal-fullscreen-md-down' : ''
        ]" :style="width ? { maxWidth: width } : {}">

            <div class="modal-content smooth-content border-0 shadow-lg rounded-4 overflow-hidden"
                :style="height ? { height: height } : {}">

                <div class="modal-header bg-transparent border-bottom">
                    <div class="d-flex flex-column pe-3">
                        <h5 :class="`modal-title fw-bold lh-sm mb-1 ${classModalTitle}`">
                            <i v-if="icon" :class="[icon, 'me-1']"></i>
                            {{ title }}
                        </h5>
                        <small v-if="subTitle" class="text-muted lh-sm" style="font-size: 0.85rem; margin-left: 30px;">
                            {{ subTitle }}
                        </small>
                    </div>
                    <button @click="closeModal" type="button" class="btn-close bg-light rounded-circle p-2 shadow-sm"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-3 px-md-4 pb-md-4 bg-transparent">
                    <slot name="body" />
                </div>

                <div v-if="footer" class="modal-footer bg-white border-top rounded-bottom-4 p-3">
                    <slot name="footer" />
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
/* =============== ANIMASI BACKDROP =============== */
.custom-modal.fade .modal-backdrop {
    transition: opacity .35s ease !important;
}

/* =============== ANIMASI MODAL CONTENT =============== */
.custom-modal .smooth-content {
    transform: translateY(-50px);
    opacity: 0;
    transition:
        transform .35s ease,
        opacity .35s ease !important;
}

/* ANIMASI MASUK (Bootstrap menambahkan .show) */
.custom-modal.show .smooth-content {
    transform: translateY(0);
    opacity: 1;
}

.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: transparent;
}

.modal-body::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.2);
    border-radius: 10px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background-color: rgba(0, 0, 0, 0.3);
}

/* Efek Blur pada Backdrop */
.modal-backdrop {
    /* Warna latar hitam transparan */
    --bs-backdrop-bg: #000;
    --bs-backdrop-opacity: 0.7;

    /* Efek Blur Kaca  */
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
}

/* Pastikan saat modal muncul, backdrop juga ikut transisi halus */
.modal-backdrop.fade {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
}

.modal-backdrop.show {
    opacity: var(--bs-backdrop-opacity);
}
</style>
