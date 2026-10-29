<script setup>
import { usePage } from "@inertiajs/vue3";
import { onBeforeUnmount, ref, watch } from "vue";
import { useToast } from "@/Composables/useToast";

const props = defineProps({
  // Durasi dalam detik (Isi 0 jika hanya ingin ditutup lewat tombol close X)
  duration: {
    type: Number,
    default: 5,
  },
});

const page = usePage();
const visible = ref(false);
const countdown = ref(props.duration);
const localFlash = ref(null);
const toastKey = ref(0);
let timer = null;
const toastMeta = (type) => {
  const map = {
    success: {
      bgClass: "callout-success",
      icon: "bi-check-circle-fill",
    },
    danger: {
      bgClass: "callout-danger",
      icon: "bi-exclamation-octagon-fill",
    },
    warning: {
      bgClass: "callout-warning",
      icon: "bi-exclamation-triangle-fill",
    },
    primary: {
      bgClass: "callout-primary",
      icon: "bi-info-circle-fill",
    },
    info: {
      bgClass: "callout-info",
      icon: "bi-info-circle-fill",
    },
  };
  return map[type] || map.primary;
};

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

  // Jika duration diset 0 (atau kurang), matikan timer.
  // Toast hanya akan tertutup jika user mengklik tombol X.
  if (props.duration <= 0) {
    return;
  }

  timer = setTimeout(() => {
    closeToast();
  }, props.duration * 1000);

  //   timer = setInterval(() => {
  //     if (countdown.value > 0) {
  //       countdown.value--;
  //     } else {
  //       closeToast();
  //     }
  //   }, 1000);
};
const resetFlash = () => {
  if (page.props.flash) {
    page.props.flash.success = null;
    page.props.flash.message = null;
    page.props.flash.error = null;
    page.props.flash.danger = null;
    page.props.flash.warning = null;
    page.props.flash.info = null;
  } else {
    page.props.errors = null;
  }
};
watch(
  () => [page.props.flash, page.props.errors],
  ([flash, errorMsg]) => {
    let message = null;
    let type = null;

    if (errorMsg && Object.keys(errorMsg).length > 0) {
      if (errorMsg.error || errorMsg.danger) {
        message = errorMsg.error || errorMsg.danger;
        type = "danger";
      } else if (errorMsg.warning) {
        message = errorMsg.warning;
        type = "warning";
      }
    }

    if (!message && flash) {
      if (flash.success || flash.message) {
        message = flash.success || flash.message;
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
    }

    if (!message) return;

    localFlash.value = {
      msg: message,
      type,
    };
    visible.value = true;
    startCountdown();
    toastKey.value++;

    resetFlash();
  },
  { deep: true, immediate: true }
);

onBeforeUnmount(() => {
  stopTimer();
});
</script>
<template>
  <Teleport to="body">
    <div
      class="toast-container position-fixed top-0 start-50 translate-middle-x p-3 w-100"
      style="z-index: 9999; pointer-events: none; max-width: 650px"
    >
      <Transition name="toast-slide">
        <div
          v-if="visible && localFlash"
          :key="toastKey"
          class="callout-toast show align-items-center rounded-3 overflow-hidden"
          :class="toastMeta(localFlash.type).bgClass"
          role="alert"
          aria-live="assertive"
          aria-atomic="true"
          style="pointer-events: auto"
        >
          <div class="d-flex flex-column">
            <div class="d-flex align-items-center py-1">
              <div class="toast-body d-flex align-items-center gap-2 fw-medium">
                <i
                  class="bi fs-6 flex-shrink-0"
                  :class="toastMeta(localFlash.type).icon"
                ></i>
                <span class="lh-sm" style="font-size: 14px">{{ localFlash.msg }}</span>
              </div>
              <button
                v-if="countdown === 0"
                type="button"
                class="btn-callout-close me-2 m-auto flex-shrink-0"
                :class="{ 'btn-close-white': localFlash.type !== 'warning' }"
                aria-label="Close"
                @click="closeToast"
              >
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
            <div
              v-if="props.duration > 0"
              :class="`toast-progress rounded-3 progress-${localFlash.type}`"
              :style="{ animationDuration: `${props.duration}s` }"
            ></div>
          </div>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>
<style scoped>
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.toast-slide-enter-from {
  opacity: 0;
  transform: translateY(-25px) scale(0.85);
}
.toast-slide-leave-to {
  opacity: 0;
  transform: translateY(0) scale(0.9);
}
.callout-toast {
  padding: 0.7rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-left-width: 0.3rem;
  position: relative;
}
.toast-container:hover {
  background-color: transparent !important;
}
.callout-primary {
  background-color: #e7f1ff;
  border-color: #c1d4f3;
  border-left-color: #3c6fe0;
  color: #3c6fe0;
}
.callout-success {
  background-color: #f1f7e9;
  border-color: #dcebcf;
  border-left-color: #9aad73;
  color: #364226;
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

/* Light - soft lavender */
.callout-light {
  background-color: #f8f9fa;
  border-color: #e3e4e5;
  border-left-color: #b8b8b8;
  color: #6c757d;
}

.btn-callout-close {
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
  transition: opacity 0.2s ease, background-color 0.2s ease, transform 0.2s ease;
}
.btn-callout-close:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.9);
  transform: scale(1.01);
}

.btn-callout-close:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 2px;
}

/* Khusus warna warning karena teksnya gelap */
.text-bg-warning .toast-progress {
  background: rgba(0, 0, 0, 0.25);
}

.toast-progress {
  height: 4px;
  width: 100%;
  animation-name: shrink-progress;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}
.progress-success {
  background-color: #198754;
}
.progress-danger {
  background-color: #dc3545;
}
.progress-warning {
  background-color: #ffc107;
}
.progress-info {
  background-color: #0d6efd;
}
.progress-light {
  background-color: #f8f9fa;
}
.progress-primary {
  background-color: #0d6efd;
}
@keyframes shrink-progress {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}
</style>
