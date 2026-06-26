<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
    duration: {
        type: Number,
        default: 5,
    },
});

const page = usePage();

const visible = ref(false);

const countdown = ref(props.duration);

const toast = ref(null);

let timer = null;

const icons = {
    success: "fa-solid fa-circle-check",
    danger: "fa-solid fa-circle-xmark",
    warning: "fa-solid fa-triangle-exclamation",
    info: "fa-solid fa-circle-info",
};

const localFlash = ref({
    msg: "",
    type: "success",
    actionLabel: null,
    actionUrl: null,
});

const stopTimer = () => {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
};

const closeToast = () => {
    stopTimer();

    visible.value = false;

    setTimeout(() => {
        localFlash.value = null;
        countdown.value = props.duration;
    }, 300);
};

const startCountdown = () => {
    stopTimer();

    countdown.value = props.duration;

    if (props.duration <= 0) {
        return;
    }

    timer = setInterval(() => {
        countdown.value--;

        if (countdown.value <= 0) {
            closeToast();
        }
    }, 1000);
};

watch(
    () => [page.props.flash, page.props.errors],
    ([flash, errors]) => {
        let message = null;
        let type = null;
        let actionLabel = null;
        let actionUrl = null;

        if (errors && Object.keys(errors).length > 0) {
            if (errors.error || errors.danger) {
                message = errors.error || errors.danger;
                type = "danger";
            } else if (errors.warning) {
                message = errors.warning;
                type = "warning";
            }
        }

        if (!message && flash) {
            if (flash.success) {
                message = flash.success;
                type = "success";
            } else if (flash.error || flash.danger) {
                message = flash.error || flash.danger;
                type = "danger";
            } else if (flash.warning) {
                message = flash.warning;
                type = "warning";
            } else if (flash.info) {
                message = flash.info;
                type = "info";
            }

            actionLabel = flash.action_label ?? null;
            actionUrl = flash.action_url ?? null;
        }

        if (!message) return;

        localFlash.value = {
            msg: message,
            type,
            actionLabel,
            actionUrl,
        };

        visible.value = true;

        startCountdown();
    },
    {
        immediate: true,
        deep: true,
    }
);

onBeforeUnmount(() => {
    stopTimer();
});
</script>

<template>
    <Teleport to="body">
        <div class="toast-wrapper">
            <Transition name="toast-animation">
                <div v-if="visible && localFlash" :class="[
                    'custom-toast',
                    `toast-${localFlash.type}`,
                ]">
                    <!-- Header -->
                    <div class="toast-header-custom">
                        <div class="d-flex align-items-center gap-2">
                            <i :class="[
                                icons[localFlash.type],
                                `icon-${localFlash.type}`,
                            ]"></i>

                            <strong class="text-capitalize">
                                {{ localFlash.type }}
                            </strong>
                        </div>

                        <button type="button" class="toast-close" @click="closeToast">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="toast-body-custom">
                        {{ localFlash.msg }}

                        <div v-if="localFlash.actionUrl" class="mt-2">
                            <Link :href="localFlash.actionUrl" class="toast-action" @click="closeToast">
                                {{
                                    localFlash.actionLabel ??
                                    "Lihat Detail"
                                }}
                            </Link>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div v-if="duration > 0" class="toast-progress" :style="{
                        width:
                            (countdown / duration) * 100 + '%',
                    }"></div>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-wrapper {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.custom-toast {
    width: 380px;
    max-width: calc(100vw - 40px);

    border-radius: 16px;

    overflow: hidden;

    backdrop-filter: blur(12px);

    box-shadow:
        0 10px 25px rgba(0, 0, 0, 0.12);

    border: 1px solid rgba(255, 255, 255, 0.3);
}

/* SUCCESS */

.toast-success {
    background: #f1f7e9;
    color: #52643a;
    border-left: 5px solid #9aad73;
}

/* ERROR */

.toast-danger {
    background: #fff0eb;
    color: #8a4b3b;
    border-left: 5px solid #d28a72;
}

/* WARNING */

.toast-warning {
    background: #fff7e8;
    color: #7a5a25;
    border-left: 5px solid #d8ad62;
}

/* INFO */

.toast-info {
    background: #f5eee8;
    color: #6f4e37;
    border-left: 5px solid #b99a80;
}

.toast-header-custom {
    padding: 12px 16px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.toast-body-custom {
    padding: 16px;
    line-height: 1.5;
    font-size: 0.92rem;
}

.toast-close {
    border: none;
    background: transparent;
    color: inherit;
    cursor: pointer;
}

.toast-close:hover {
    opacity: 0.8;
}

.toast-progress {
    height: 4px;
    background: currentColor;
    transition: width 1s linear;
}

.toast-action {
    font-weight: 600;
    color: inherit;
    text-decoration: underline;
}

.icon-success {
    color: #7ca05d;
}

.icon-danger {
    color: #d28a72;
}

.icon-warning {
    color: #d8ad62;
}

.icon-info {
    color: #b99a80;
}

/* Animation */

.toast-animation-enter-active,
.toast-animation-leave-active {
    transition: all 0.35s ease;
}

.toast-animation-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-animation-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
