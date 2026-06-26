<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
const props = defineProps({
    roles: Array,
    branches: Array
})
const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
})
const isSubmit = () => {
    form.post(route('register.store'), {
        onFinish: () => form.reset("password")
    })
}
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const togglePasswordVisibilty = () => {
    showPassword.value = !showPassword.value
}
const togglePasswordConfirm = () => {
    showPasswordConfirm.value = !showPasswordConfirm.value
}

const inputName = ref(null);
onMounted(() => {
    inputName.value.focus();
})
</script>
<template>

    <Head title="Register" />
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-xl-6">

                <callout />

                <div class="card overflow-auto shadow-sm rounded rounded-4 p-xl-4 p-0">
                    <div class="card-title text-center mb-0 py-3">
                        <h2 class="fw-bold text-capitalize">Buat Akun</h2>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-grid mb-3 align-items-center">
                            <a :href="route('google.login')" class="btn btn-app">
                                <img src="assets/icon/google.svg" class="img-fluid img-icon" alt="">
                                Daftar Dengan Google
                            </a>
                        </div>
                        <hr />
                        <form-wrapper @submit="isSubmit">
                            <div class="row g-2">
                                <div class="col-xl-12">
                                    <div class="mb-2">
                                        <input-label for="name" value="Nama" class="fw-semibold" />
                                        <div class="position-relative">
                                            <i class="fas fa-user input-icon-left"></i>
                                            <text-input placeholder="Nama Pengguna" ref="inputName"
                                                :class="['input-fixed-height']" type="text" name="name"
                                                v-model="form.name" />
                                        </div>
                                        <input-error :message="form.errors.name" />
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <div class="mb-2">
                                        <input-label for="email" value="Email" class="fw-semibold" />
                                        <div class="position-relative">
                                            <i class="fas fa-envelope input-icon-left"></i>
                                            <text-input placeholder="Email Pengguna" :class="['input-fixed-height']"
                                                type="email" name="email" v-model="form.email" />
                                        </div>
                                        <input-error :message="form.errors.email" />
                                    </div>
                                </div>
                                <div class="col-xl-12">
                                    <div class="mb-2">
                                        <input-label for="password" value="Kata sandi" class="fw-semibold" />
                                        <div class="position-relative">
                                            <i class="fas fa-lock input-icon-left"></i>
                                            <text-input placeholder="Kata Sandi"
                                                :type="showPassword ? 'text' : 'password'"
                                                :class="['input-fixed-height', 'has-icon-right']" autocomplete="off"
                                                v-model="form.password" name="password" />
                                            <i @click="togglePasswordVisibilty"
                                                :class="['input-icon-right', 'cursor-pointer', showPassword ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                                        </div>
                                        <input-error :message="form.errors.password" />
                                    </div>
                                </div>

                                <div class="col-xl-12">
                                    <div class="mb-3">
                                        <input-label for="password_confirmation" value="Ulangi kata sandi"
                                            class="fw-semibold" />
                                        <div class="position-relative">
                                            <i class="fas fa-lock input-icon-left"></i>
                                            <text-input placeholder="Ulangi Kata Sandi"
                                                :type="showPasswordConfirm ? 'text' : 'password'"
                                                :class="['input-fixed-height', 'has-icon-right']" autocomplete="off"
                                                v-model="form.password_confirmation" name="password_confirmation" />
                                            <i @click="togglePasswordConfirm"
                                                :class="['input-icon-right', 'cursor-pointer', showPasswordConfirm ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                                        </div>
                                        <input-error :message="form.errors.password_confirmation" />
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 d-grid">
                                <base-button waiting="Memproses..." :loading="form.processing"
                                    class="bg-gradient border btn-height-1" type="submit" name="submit"
                                    label="Daftar" />
                            </div>
                        </form-wrapper>
                        <div class="mb-3 d-flex justify-content-center align-items-center px-2 gap-1">
                            <span>
                                Sudah punya akun?
                            </span>
                            <span>
                                <Link :href="route('login')" class="text-decoration-none">Masuk</Link>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
