<script setup>
import { ref, watch } from "vue";
const props = defineProps({
    message: {
        type: String,
        default: "",
    },
    variant: {
        type: String,
        default: "success",
    },
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
        default: 5,
    },
});
// Mengontrol visibilitas alert
const visible = ref(false);
const countdown = ref(props.duration);
let timer;
// Mengubah nilai visible jika message berubah
watch(
    () => props.message,
    (newValue) => {
        if (newValue) {
            visible.value = true;
            countdown.value = props.duration;

            clearInterval(timer)
            timer = setInterval(() => {
                countdown.value--
                if (countdown.value <= 0) {
                    visible.value = false
                    clearInterval(timer)
                }
            }, 1000);
        }
    },
    { immediate: true }
);
</script>
<template>
    <transition name="fade-alert" appear>
        <div :class="['alert rounded-1', `alert-${variant}`, 'fw-bold', 'd-flex', 'justify-content-between', 'align-items-center']"
            role="alert" v-if="visible">
            <div class="d-flex align-items-center gap-2">
                <i v-if="variant" :class="icon[variant]"></i>
                {{ message }}
            </div>
            <span class="small fw-bold">{{ countdown }}</span>
        </div>
    </transition>
</template>
<style scoped>
/* Transisi dengan class bawaan Vue */
.fade-alert-enter-active,
.fade-alert-leave-active {
    transition: all 0.5s ease-in-out;
}

.fade-alert-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-alert-enter-to {
    opacity: 1;
    transform: translateY(0);
}

.fade-alert-leave-from {
    opacity: 1;
    transform: translateY(0);
}

.fade-alert-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
