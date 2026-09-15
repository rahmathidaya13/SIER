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
        onError: () => form.reset("password")
    });
};
const showPassword = ref(false);
const togglePasswordVisibilty = () => {
    showPassword.value = !showPassword.value;
};

const inputEmail = ref(null);
onMounted(() => {
    inputEmail.value.focus();
})

</script>
<template>

    <Head title="Masuk" />
    <div class="container-fluid vh-100 overflow-hidden">
        <div class="row vh-100 justify-content-center align-items-center">
            <div class="col-12 col-xl-4 col-md-6 col-lg-6">
                <callout :icon="false" :duration="0" />
                <div class="card p-3 p-3 p-xl-4 shadow-sm border">
                    <div class="text-center mb-0">
                        <h2 class="fw-bold mb-1">Selamat Datang</h2>
                        <p class="text-muted small">Silakan masuk menggunakan akun internal Anda</p>
                    </div>
                    <div class="card-body">
                        <form-wrapper @submit.prevent="isSubmit">
                            <div class="form-floating mb-3">
                                <input-text :error="form.errors.email" ref="inputEmail" name="email"
                                    v-model="form.email" inputClass="focus-ring focus-ring-primary"
                                    placeholder="name@example.com" autocomplete="username" />
                                <label for="email_input">Alamat Email</label>
                                <input-error :message="form.errors.email" />
                            </div>

                            <div class="mb-3">
                                <div v-if="form.password" class="d-flex justify-content-end mb-1">
                                    <span @click="togglePasswordVisibilty"
                                        class="small text-primary cursor-pointer">Lihat
                                        kata sandi</span>
                                </div>
                                <div class="form-floating">
                                    <input-text :error="form.errors.password" name="password" v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        inputClass="focus-ring focus-ring-primary" placeholder="Password" />
                                    <label for="password_input">Kata Sandi</label>
                                    <input-error :message="form.errors.password" />
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <check-box :value="form.remember" v-model:checked="form.remember" name="remember"
                                    label="Ingatkan saya" />
                                <Link :href="route('forgot.create')" class="text-decoration-none">Lupa kata sandi?
                                </Link>
                            </div>

                            <button type="submit"
                                class="btn btn-primary bg-gradient w-100 py-3 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2"
                                :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm"
                                    role="status"></span>
                                <span>{{ form.processing ? 'Memeriksa Kredensial...' : 'Masuk' }}</span>
                                <i v-if="!form.processing" class="bi bi-arrow-right"></i>
                            </button>

                        </form-wrapper>
                        <div class="text-center mt-3">
                            <p class="text-muted small">
                                Daftar sebagai karyawan
                                <Link :href="route('register')">Form pendaftaran
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
/* Sedikit pemanis bawaan biar inputan Bootstrapnya terasa 'mahal' */
.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}
</style>
