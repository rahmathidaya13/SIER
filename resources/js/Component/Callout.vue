<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { onBeforeUnmount, ref, watch } from "vue";
const props = defineProps({
    icon: {
        type: Object,
        default: {
            success: "fas fa-check-circle text-success",
            danger: "fas fa-times-circle text-danger",
            warning: "fas fa-exclamation-circle text-warning",
            info: "fas fa-info-circle text-info",
        },
    },
    duration: {
        type: Number,
        default: 10,
    },
})

const page = usePage();
const localFlash = ref(null); // Menyimpan flash message lokal untuk deteksi perubahan
const visible = ref(false);
const countdown = ref(props.duration);

let timer = null;

/**
 * Menghentikan interval countdown.
 */
const stopTimer = () => {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

const closeCallout = () => {
    stopTimer();
    visible.value = false;

    /*
    * Tunggu animasi transition selesai sebelum
    * menghapus data callout.
    */
    window.setTimeout(() => {
        localFlash.value = null;
        countdown.value = props.duration;
    }, 400);
}

/**
 * Memulai countdown dari awal.
 */
const startCountdown = () => {
    stopTimer();

    countdown.value = props.duration;

    /*
     * Jika duration <= 0, callout tidak ditutup otomatis.
     * Jadi hanya bisa ditutup melalui tombol close.
     */
    if (props.duration <= 0) {
        return;
    }

    timer = setInterval(() => {
        countdown.value--;

        if (countdown.value <= 0) {
            closeCallout();
        }
    }, 1000);
};


//  Logika Deteksi Flash Message
watch(() => [page.props.flash, page.props.errors], ([flash, errors]) => {
    let incomingMsg = null;
    let incomingType = null;
    let incomingActionLabel = null;
    let incomingActionUrl = null;

    // 1. TANGKAP ERRORS
    if (errors && Object.keys(errors).length > 0) {
        if (errors.error || errors.danger) {
            incomingMsg = errors.error || errors.danger;
            incomingType = 'danger';
            // Consume: Hapus dari page props agar tidak diulang
            if (errors.error) page.props.errors.error = null;
            if (errors.danger) page.props.errors.danger = null;
        } else if (errors.warning) {
            incomingMsg = errors.warning;
            incomingType = 'warning';
            page.props.errors.warning = null;
        }
    }

    /*
    * Tangkap flash message.
    */
    if (!incomingMsg && flash && Object.keys(flash).length > 0) {

        if (flash.success) {
            incomingMsg = flash.success;
            incomingType = 'success';

            incomingActionLabel = flash.action_label ?? null;
            incomingActionUrl = flash.action_url ?? null;
            page.props.flash.success = null;
            page.props.flash.action_label = null;
            page.props.flash.action_url = null;
        }
        else if (flash.error || flash.danger) {
            incomingMsg = flash.error || flash.danger;
            incomingType = 'danger';
            incomingActionLabel = flash.action_label ?? null;
            incomingActionUrl = flash.action_url ?? null;
            page.props.flash.error = null;
            page.props.flash.danger = null;
            page.props.flash.action_label = null;
            page.props.flash.action_url = null;
        }
        else if (flash.warning) {
            incomingMsg = flash.warning;
            incomingType = 'warning';
            incomingActionLabel = flash.action_label ?? null;
            incomingActionUrl = flash.action_url ?? null;
            page.props.flash.warning = null;
            page.props.flash.action_label = null;
            page.props.flash.action_url = null;
        }
        else if (flash.info) {
            incomingMsg = flash.info;
            incomingType = 'info';
            incomingActionLabel = flash.action_label ?? null;
            incomingActionUrl = flash.action_url ?? null;
            page.props.flash.info = null;
            page.props.flash.action_label = null;
            page.props.flash.action_url = null;
        }
        else if (flash.message) {
            incomingMsg = flash.message;
            incomingType = 'success';
            incomingActionLabel = flash.action_label ?? null;
            incomingActionUrl = flash.action_url ?? null;
            page.props.flash.message = null;
            page.props.flash.action_label = null;
            page.props.flash.action_url = null;
        }
    }

    // Jika tidak ada pesan baru (misal: efek dari pencarian keyword),
    // HENTIKAN PROSES di sini agar callout lama tidak terganggu atau muncul ulang.
    if (!incomingMsg) return;
    stopTimer();

    localFlash.value = {
        msg: incomingMsg,
        type: incomingType,
        actionLabel: incomingActionLabel,
        actionUrl: incomingActionUrl,
    };
    visible.value = true;
    startCountdown();



}, { immediate: true });

onBeforeUnmount(() => {
    stopTimer();
});
</script>

<template>
    <transition name="fade-alert" appear>
        <div :class="['callout rounded-3 align-items-center d-flex justify-content-between', `callout-${localFlash.type}`]"
            v-if="localFlash && visible">
            <div class="d-flex align-items-center gap-2 fw-semibold flex-wrap">
                <i v-if="localFlash.type" :class="icon[localFlash.type]"></i>
                <span>
                    {{ localFlash.msg }}
                </span>
                <Link v-if="localFlash.actionUrl" :href="localFlash.actionUrl" class="callout-action"
                    @click="closeCallout">
                    {{ localFlash.actionLabel ?? 'Lihat detail' }}
                    <i class="bi bi-arrow-right-short"></i>
                </Link>

            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span v-if="duration > 0" class="callout-countdown" title="Callout akan tertutup otomatis">
                    {{ countdown }}
                </span>

                <button v-if="!duration" type="button" class="callout-close" aria-label="Tutup notifikasi" title="Tutup"
                    @click="closeCallout">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </transition>
</template>
<style scoped>
.fade-alert-enter-active,
.fade-alert-leave-active {
    transition: all 0.4s ease-in-out;
}

.fade-alert-enter-from,
.fade-alert-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

.fade-alert-enter-to,
.fade-alert-leave-from {
    opacity: 1;
    transform: translateY(0);
}

.callout {
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-left-width: 0.25rem;
    position: relative;
    z-index: 999;
    box-shadow: 0 8px 20px rgba(43, 27, 18, 0.06);
}

/* Success - soft olive latte */
.callout-success {
    background-color: #f1f7e9;
    border-color: #dcebcf;
    border-left-color: #9aad73;
    color: #52643a;
}

/* Danger - soft terracotta cream */
.callout-danger {
    background-color: #fff0eb;
    border-color: #f6d5ca;
    border-left-color: #d28a72;
    color: #8a4b3b;
}

/* Warning - soft caramel cream */
.callout-warning {
    background-color: #fff7e8;
    border-color: #f3dfb8;
    border-left-color: #d8ad62;
    color: #7a5a25;
}

/* Info - soft mocha beige */
.callout-info {
    background-color: #f5eee8;
    border-color: #e5d4c6;
    border-left-color: #b99a80;
    color: #6f4e37;
}

.callout-countdown {
    min-width: 28px;
    height: 28px;
    padding: 0 7px;
    border-radius: 50rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.65);
    border: 1px solid rgba(43, 27, 18, 0.08);
}

.callout-close {
    width: 30px;
    height: 30px;
    padding: 0;
    border: 1px solid #cac9c9;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.55);
    color: currentColor;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    opacity: 0.65;
    transition:
        opacity 0.2s ease,
        background-color 0.2s ease,
        transform 0.2s ease;
}

.callout-close:hover {
    opacity: 1;
    background: rgba(255, 255, 255, 0.9);
    transform: scale(1.05);
}

.callout-close:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.callout-action {
    color: currentColor;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 3px;
    white-space: nowrap;
    opacity: 0.85;
}

.callout-action:hover {
    color: currentColor;
    opacity: 1;
}
</style>
