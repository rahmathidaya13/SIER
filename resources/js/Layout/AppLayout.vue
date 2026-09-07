<script setup>
import { ref, watch, onUnmounted, onMounted, provide } from "vue";
import { usePage } from "@inertiajs/vue3";
import Sidebar from "./Sidebar.vue";
import Header from "./Header.vue";
import { useConfirm } from "@/Composables/useConfirm";
import { useNotification } from "@/Composables/useNotification";
import FormModalProfile from "../Pages/Profile/FormModalProfile.vue";
const { state } = useConfirm();

const page = usePage();
const userId = page.props.auth.user.id;

const sideBarOpen = ref(window.innerWidth >= 992);

const {
    unreadCount,
    setUnreadCount,
    incrementUnread
} = useNotification();

const toggleSidebar = () => {
    sideBarOpen.value = !sideBarOpen.value;
};
const closeSideBar = () => {
    sideBarOpen.value = false;
};
watch(sideBarOpen, (value) => {
    if (window.innerWidth < 992) {
        document.body.style.overflow = value ? "hidden" : "";
    } else {
        // Jika di PC/Desktop, pastikan halaman selalu bisa di-scroll
        document.body.style.overflow = "";
    }
});
onUnmounted(() => {
    if (window.Echo) {
        window.Echo?.leave(`App.Models.User.${userId}`);
    }
    document.body.style.overflow = "";
});

const isProfileModalOpen = ref(false);

let notificationChannel = null;
onMounted(() => {

    setUnreadCount(page.props.auth.unread_notifications_count || 0);

    if (!window.Echo) {
        return;
    }

    notificationChannel = window.Echo.private(
        `App.Models.User.${userId}`
    );

    notificationChannel.notification((notification) => {
        // 1. Munculkan Toast/Snackbar di pojok layar
        console.log("NOTIFIKASI BARU MASUK:", notification);
        // alert(notification.message); // Ganti dengan library Toast Anda (misal: vue-toastification)
        incrementUnread();
    });
});
</script>
<template>
    <!-- Confirm Modal -->
    <Teleport to="body">
        <div v-if="state.show" class="confirm-overlay d-flex align-items-center justify-content-center">
            <div class="confirm-card bg-white rounded-4 shadow-lg p-4">
                <div class="text-center mb-4">
                    <div :class="`icon-circle bg-${state.variantIcon ?? state.variant
                        } bg-opacity-10 text-${state.variantIcon ?? state.variant} mb-3`">
                        <i :class="` ${state.icon ?? 'fas fa-exclamation-triangle'} fa-2x`"></i>
                    </div>
                    <h5 class="fw-bold">{{ state.title }}</h5>
                    <p class="text-muted mb-0 px-2" :style="{ fontSize: state.messageFontSize }">
                        {{ state.message }}
                    </p>
                </div>

                <div v-if="state.requireCheckbox" class="bg-light p-3 rounded-3 mb-4 border border-light-subtle">
                    <div class="form-check d-flex align-items-center gap-2">
                        <input class="form-check-input mt-0 custom-checkbox" type="checkbox" v-model="state.isChecked"
                            id="confirmCheck" style="width: 1.2rem; height: 1.2rem; cursor: pointer" />
                        <label class="form-check-label small fw-medium text-dark cursor-pointer" for="confirmCheck">
                            {{ state.checkboxText }}
                        </label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button @click="state.onCancel"
                        class="btn btn-light btn-sm rounded-pill flex-fill py-2 text-muted fw-semibold">
                        {{ state.cancelText }}
                    </button>
                    <button @click="state.onConfirm" v-if="state.showButtonConfirm"
                        :disabled="state.requireCheckbox && !state.isChecked" :class="`btn btn-${state.variantButton ?? state.variant
                            } rounded-pill btn-sm flex-fill py-2 fw-semibold shadow-sm`">
                        {{ state.confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
    <div class="sidebar-backdrop" :class="{ show: sideBarOpen }" role="presentation" @click="closeSideBar"></div>

    <div class="d-flex wrapper">
        <Sidebar @openProfileModal="isProfileModalOpen = true" :isOpen="sideBarOpen" @close="closeSideBar" />

        <div class="page-content-wrapper" :class="{ expanded: !sideBarOpen }">
            <Header @close="toggleSidebar" />
            <main class="container-fluid px-3 py-4">
                <slot name="content" />
            </main>
        </div>
    </div>
    <FormModalProfile v-model:show="isProfileModalOpen" />
</template>
<style scoped>
.wrapper {
    min-height: 100vh;
    width: 100%;
}

.page-content-wrapper {
    transition: margin-left 0.35s cubic-bezier(0.2, 0, 0, 1),
        width 0.35s cubic-bezier(0.2, 0, 0, 1);
    width: 100%;
}

.sidebar-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.4);
    z-index: 1030;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.35s ease, visibility 0.35s ease;
}

.sidebar-backdrop.show {
    opacity: 1;
    visibility: visible;
}

@media (min-width: 992px) {
    .page-content-wrapper {
        margin-left: var(--sidebar-width);
        /* KUNCI PERBAIKAN: Kurangi lebar 100% dengan lebar sidebar */
        width: calc(100% - var(--sidebar-width));
    }

    .page-content-wrapper.expanded {
        margin-left: 0 !important;
        /* Saat sidebar ditutup, kembalikan lebarnya penuh ke 100% */
        width: 100% !important;
    }

    .sidebar-backdrop,
    .sidebar-backdrop.show {
        display: none !important;
    }
}

@media (max-width: 991.98px) {
    .page-content-wrapper {
        margin-left: 0 !important;
    }
}

/* confirm overlay */
.confirm-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease-out;
}

.confirm-card {
    width: 100%;
    max-width: 450px;
    border: none;
    animation: zoomIn 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

/* Keyframes Animasi */
@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.9);
        /* Mulai dari sedikit kecil */
    }

    to {
        opacity: 1;
        transform: scale(1);
        /* Kembali ke ukuran asli */
    }
}

.custom-checkbox {
    width: 1.2em;
    height: 1.2em;
    cursor: pointer;
    border-radius: 4px;
}

.custom-checkbox:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Efek saat tombol disabled */
button:disabled {
    cursor: not-allowed;
    filter: grayscale(1);
    opacity: 0.6;
}
</style>
