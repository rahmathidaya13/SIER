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
      bgClass: "text-bg-success",
      icon: "bi-check-circle-fill",
    },
    danger: {
      bgClass: "text-bg-danger",
      icon: "bi-exclamation-octagon-fill",
    },
    warning: {
      bgClass: "text-bg-warning text-dark",
      icon: "bi-exclamation-triangle-fill",
    },
    primary: {
      bgClass: "text-bg-primary",
      icon: "bi-info-circle-fill",
    },
    info: {
      bgClass: "text-bg-info",
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
watch(
  () => [page.props.flash, page.props.errors],
  ([flash, errorMsg]) => {
    console.log(flash);
    let message = null;
    let type = "info";

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
      class="toast-container position-fixed top-0 end-0 p-3"
      style="z-index: 9999; pointer-events: none"
    >
      <Transition name="toast-slide">
        <div
          v-if="visible && localFlash"
          :key="toastKey"
          class="toast show align-items-center border-0 rounded-3 overflow-hidden"
          :class="toastMeta(localFlash.type).bgClass"
          role="alert"
          aria-live="assertive"
          aria-atomic="true"
          style="pointer-events: auto; min-width: 500px; max-width: 600px;"
        >
          <div class="d-flex flex-column">
            <div class="d-flex align-items-center py-1">
              <div class="toast-body d-flex align-items-center gap-2 fw-medium">
                <i
                  class="bi fs-6 flex-shrink-0"
                  :class="toastMeta(localFlash.type).icon"
                ></i>
                <span class="lh-sm" style="font-size: 14px;">{{ localFlash.msg }}</span>
              </div>
              <button
                v-if="countdown === 0"
                type="button"
                class="btn-close me-2 m-auto flex-shrink-0"
                :class="{ 'btn-close-white': localFlash.type !== 'warning' }"
                aria-label="Close"
                @click="closeToast"
              ></button>
            </div>
            <div
              v-if="props.duration > 0"
              class="toast-progress rounded-3"
              :style="{ animationDuration: `${props.duration}s` }"
            ></div>
          </div>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>
<style scoped>
/* Animasi muncul dari kanan & hilang halus */
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.toast-slide-enter-from {
  opacity: 0;
  transform: translateX(60px) scale(0.95);
}
.toast-slide-leave-to {
  opacity: 0;
  transform: translateX(60px) scale(0.9);
}
.toast-progress {
  height: 5px;
  background: rgb(238, 237, 237);
  width: 100%;
  animation-name: shrink-progress;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

/* Khusus warna warning karena teksnya gelap */
.text-bg-warning .toast-progress {
  background: rgba(0, 0, 0, 0.25);
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
