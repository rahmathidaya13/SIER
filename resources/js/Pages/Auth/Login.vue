<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref, watch } from "vue";
const form = useForm({
  email: "",
  password: "",
  remember: false,
});
const isSubmit = () => {
  form.post(route("login.store"), {
    onFinish: () => form.reset("password"),
    onError: () => form.reset("password"),
  });
};
const showPassword = ref(false);
const togglePasswordVisibilty = () => {
  showPassword.value = !showPassword.value;
};

const inputEmail = ref(null);
onMounted(() => {
  inputEmail.value.focus();
});
</script>
<template>
  <Head title="Masuk" />
  <div class="login-wrapper vh-100 d-flex justify-content-center align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5 col-xl-4">
          <callout :icon="false" :duration="0" />
          <div class="card shadow-xl border-0 rounded-4 overflow-hidden">
            <div class="card-body p-3 p-sm-4">
              <div class="text-center mb-4 pb-2">
                <div
                  class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3 shadow-sm"
                  style="width: 64px; height: 64px"
                >
                  <i class="bi bi-shield-lock-fill fs-2"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Selamat Datang</h3>
                <p class="text-muted small mb-0">
                  Silakan masuk dengan kredensial internal Anda
                </p>
              </div>
              <form-wrapper @submit.prevent="isSubmit">
                <div class="mb-3">
                  <input-label labelClass="mb-2" for="email_input">
                    Alamat Email
                  </input-label>
                  <div class="position-relative">
                    <i class="bi bi-envelope-fill input-icon-left"></i>
                    <input-text
                      type="email"
                      :error="form.errors.email"
                      ref="inputEmail"
                      name="email"
                      v-model="form.email"
                      inputClass="input-fixed-height input-height-1"
                      placeholder="name@example.com"
                      autocomplete="username"
                    />
                  </div>
                  <input-error :message="form.errors.email" />
                </div>

                <div class="mb-3">
                  <div
                    class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1"
                  >
                    <input-label for="password_input" value="Kata sandi" />
                    <Link
                      :href="route('forgot.create')"
                      class="text-decoration-none small"
                      >Lupa kata sandi?
                    </Link>
                  </div>
                  <div class="position-relative">
                    <i class="bi bi-lock-fill input-icon-left"></i>
                    <input-text
                      :error="form.errors.password"
                      placeholder="Minimal 8 karakter"
                      :type="showPassword ? 'text' : 'password'"
                      :inputClass="[
                        'input-fixed-height',
                        'has-icon-right',
                        'input-height-1',
                      ]"
                      autocomplete="off"
                      v-model="form.password"
                      name="password"
                      :isValid="false"
                    />
                    <i
                      @click="togglePasswordVisibilty"
                      :class="[
                        'input-icon-right',
                        'cursor-pointer',
                        showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill',
                      ]"
                    ></i>
                  </div>
                  <input-error :message="form.errors.password" />
                </div>
                <button
                  type="submit"
                  class="btn btn-primary bg-gradient w-100 py-2 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2"
                  :disabled="form.processing"
                >
                  <span
                    v-if="form.processing"
                    class="spinner-border spinner-border-sm"
                    role="status"
                  ></span>
                  <span>{{ form.processing ? "Memeriksa Kredensial..." : "Masuk" }}</span>
                  <i v-if="!form.processing" class="bi bi-arrow-right"></i>
                </button>
              </form-wrapper>
              <div class="text-center mt-3">
                <p class="text-muted small">
                  Daftar sebagai karyawan
                  <Link :href="route('register')">Form pendaftaran </Link>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
<style scoped>
.login-wrapper {
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
}
.shadow-xl {
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.06);
}
</style>
