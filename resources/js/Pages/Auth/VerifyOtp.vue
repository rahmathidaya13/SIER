<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from "vue";
import { router, useForm } from "@inertiajs/vue3";

const props = defineProps({
  email: {
    type: String,
    required: true,
  },
});

const otp = ref("");
const countdown = ref(60);
let timer = null;

const form = useForm({
  otp: "",
});

const canResend = computed(() => {
  return countdown.value <= 0;
});

const formattedCountdown = computed(() => {
  const minutes = Math.floor(countdown.value / 60);
  const seconds = countdown.value % 60;

  return `${minutes}:${seconds.toString().padStart(2, "0")}`;
});

const verifyOtp = () => {
  form.otp = otp.value;

  form.post(route("verification.otp.verify"), {
    preserveScroll: true,
    replace: true,
    onSuccess: () => {
      otp.value = "";
    },
  });
};

const resendOtp = () => {
  if (!canResend.value) {
    return;
  }

  router.post(
    route("verification.otp.resend"),
    {},
    {
      preserveScroll: true,

      onSuccess: () => {
        startCountdown();
        otp.value = "";
      },
      onError: (errors) => {
        // Tangkap pesan error cooldown 60 detik dari backend
        if (errors.otp) {
          // Karena form.errors read-only jika pakai router.post,
          // kita injeksi error-nya secara manual agar muncul di bawah input
          form.setError("otp", errors.otp);
        }
      },
    }
  );
};

const startCountdown = () => {
  countdown.value = 60;

  if (timer) {
    clearInterval(timer);
  }

  timer = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--;
    } else {
      clearInterval(timer);
    }
  }, 1000);
};

const handleInput = (event) => {
  otp.value = event.target.value.replace(/\D/g, "").slice(0, 6);
};

const otpInput = ref(null);
onMounted(() => {
  startCountdown();
  nextTick(() => {
    otpInput.value.focus();
  });
});

onUnmounted(() => {
  if (timer) {
    clearInterval(timer);
  }
});
</script>
<template>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5 col-lg-4">
        <callout :icon="false" :duration="0" />
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
          <div class="card-body p-4">
            <div class="d-flex justify-content-center mb-4">
              <div
                class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center shadow-sm"
                style="width: 72px; height: 72px"
              >
                <i class="bi bi-envelope-paper-heart fs-1"></i>
              </div>
            </div>

            <div class="text-center mb-4">
              <h4 class="fw-bold text-dark mb-2">Verifikasi OTP</h4>
              <p class="text-muted mb-0" style="font-size: 14.5px; line-height: 1.6">
                Masukkan 6 digit kode OTP yang telah kami kirimkan ke email
                <br />
                <strong class="text-dark">{{ props.email }}</strong>
              </p>
            </div>

            <form-wrapper @submit.prevent="verifyOtp">
              <div class="mb-3">
                <input-number
                  placeholder="******"
                  ref="otpInput"
                  name="otp_code"
                  autocomplete="one-time-code"
                  :maxChar="6"
                  style="letter-spacing: 10px; font-weight: 700; font-size: 24px"
                  v-model="otp"
                  inputClass="text-center bg-light py-3 text-primary shadow-none otp-input-field rounded-3"
                  @input="handleInput"
                  @keyup.enter="verifyOtp"
                  :isValid="false"
                  :error="form.errors.otp"
                />
                <input-error :message="form.errors.otp" />
              </div>
            </form-wrapper>

            <button
              type="button"
              class="btn btn-primary w-100 py-2.5 rounded-pill fw-semibold shadow-sm transition-all btn-verify"
              :disabled="form.processing || otp.length !== 6"
              @click="verifyOtp"
            >
              <span v-if="form.processing">
                <i class="bi bi-arrow-repeat spin-icon me-2"></i> Memverifikasi...
              </span>
              <span v-else> Verifikasi <i class="bi bi-arrow-right ms-1"></i> </span>
            </button>

            <div class="text-center mt-4 pt-3 border-top border-light-subtle">
              <p class="text-muted small mb-2">Belum menerima kode OTP?</p>

              <!-- State: Resend Available -->
              <button
                v-if="canResend"
                type="button"
                class="btn btn-link p-0 text-decoration-none fw-bold text-primary"
                @click="resendOtp"
              >
                <i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang Kode
              </button>

              <!-- State: Countdown Active -->
              <span
                v-else
                class="text-muted small d-inline-flex align-items-center bg-light px-3 py-1.5 rounded-pill"
              >
                <i class="bi bi-clock-history me-2 text-secondary"></i>
                Kirim ulang dalam
                <strong class="ms-1 text-dark">{{ formattedCountdown }}</strong>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
/* Transisi mulus untuk semua interaksi tombol */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Animasi Putar untuk loading */
.spin-icon {
  display: inline-block;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}

/* Custom styling untuk input OTP agar terlihat seperti kotak premium */
:deep(.otp-input-field) {
  transition: all 0.2s ease;
}
:deep(.otp-input-field:focus) {
  background-color: #ffffff !important;
  box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15) !important;
  border: 1px solid #0d6efd !important;
}

/* Hover efek tombol submit */
.btn-verify:not(:disabled):hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(13, 110, 253, 0.25) !important;
}
</style>
