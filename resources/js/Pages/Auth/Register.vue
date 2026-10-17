<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
const props = defineProps({
    branches: Array,
    roles: Array
})
const form = useForm({
    name: "",
    email: "",
    role: null,
    id_branch: null,
    branch_code: "",
    password: "",
    password_confirmation: "",
})
const roleOptions = computed(() => [
    {
        label: 'Belum ada pilihan',
        value: '',
    },
    ...props.roles.map(data => ({
        label: data.name,
        value: data.name,
    }))
])
const branchName = ref('');
const branchOptions = computed(() => [
    {
        label: 'Belum ada pilihan',
        value: '',
    },
    ...props.branches.map(data => ({
        label: data.name,
        value: data.id_branch,
    }))
])
const isSubmit = () => {
    form.post(route('register.store'), {
        onFinish: () => form.reset('password', 'password_confirmation')
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

watch(() => form.id_branch, (value) => {
    const branch = props.branches.find(branch => branch.id_branch === value);
    branchName.value = branch ? branch.name : '';
})

const passwordStrength = computed(() => {
    const password = form.password;

    if (!password) {
        return {
            label: '',
            width: 0,
            color: ''
        }
    }

    let score = 0;

    if (password.length >= 8) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    let label = '';
    let width = 0;
    let color = '';

    switch (score) {
        case 1:
            label = 'Sangat Lemah';
            width = 20;
            color = 'bg-danger';
            break;
        case 2:
            label = 'Lemah';
            width = 40;
            color = 'bg-warning';
            break;
        case 3:
            label = 'Sedang';
            width = 60;
            color = 'bg-info';
            break;
        case 4:
            label = 'Kuat';
            width = 80;
            color = 'bg-primary';
            break;
        case 5:
            label = 'Sangat Kuat';
            width = 100;
            color = 'bg-success';
            break;
        default:
            label = '';
            width = 0;
            color = '';
    }

    return { label, width, color };

})

</script>
<template>

    <Head title="Pendaftaran" />
    <div class="container py-3">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-xl-8 col-12">

                <callout :duration="0" />

                <div class="card overflow-auto border shadow-sm rounded rounded-4 p-xl-4 p-0">
                    <div class="card-title text-center mb-0 py-3">
                        <h2 class="fw-bold text-capitalize">Pendaftaran</h2>
                        <p class="text-muted small">Silakan lengkapi data diri dan penempatan cabang Anda.</p>
                    </div>
                    <div class="card-body pt-0">
                        <form-wrapper @submit="isSubmit">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <div class="mb-1">
                                        <input-label labelClass="mb-2" for="name_input" value="Nama" />
                                        <div class="position-relative">
                                            <i class="fas fa-user input-icon-left"></i>
                                            <input-text :error="form.errors.name" placeholder="Nama Pengguna"
                                                ref="inputName" inputClass="input-fixed-height input-height-1"
                                                name="name" v-model="form.name" />
                                        </div>
                                        <input-error :message="form.errors.name" />
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-1">
                                        <input-label labelClass="mb-2" for="email_input" value="Email"
                                            class="fw-semibold" />
                                        <div class="position-relative">
                                            <i class="fas fa-envelope input-icon-left"></i>
                                            <input-text :error="form.errors.email" placeholder="Email Pengguna"
                                                inputClass="input-fixed-height input-height-1" type="email" name="email"
                                                v-model="form.email" />
                                        </div>
                                        <input-error :message="form.errors.email" />
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-1">
                                        <input-label labelClass="mb-2" for="role_select" value="Posisi / Jabatan" />
                                        <select-input selectClass="input-fixed-height input-height-1"
                                            :error="form.errors.role" name="role" text="-- Pilih Jabatan --"
                                            :options="roleOptions" v-model="form.role" />
                                        <input-error :message="form.errors.role" />
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-1">
                                        <input-label labelClass="mb-2" for="id_branch_select"
                                            value="Penempatan Cabang" />
                                        <select-input selectClass="input-fixed-height input-height-1"
                                            :error="form.errors.id_branch" name="id_branch" text="-- Pilih Cabang --"
                                            :options="branchOptions" v-model="form.id_branch" />
                                        <input-error :message="form.errors.id_branch" />
                                    </div>
                                </div>

                                <div class="col-12" v-if="form.id_branch">
                                    <div class="p-3 bg-light rounded-3 border border-warning-subtle">
                                        <input-label labelClass="mb-2" for="branch_code_input"
                                            :value="'Kode Verifikasi Cabang (' + branchName + ')'"
                                            class="fw-semibold text-warning-emphasis" />
                                        <div class="position-relative mt-1">
                                            <i class="fas fa-key input-icon-left text-muted"></i>
                                            <input-text :error="form.errors.branch_code"
                                                placeholder="Masukkan Kode Cabang..." inputClass="input-fixed-height"
                                                name="branch_code" v-model="form.branch_code" />
                                        </div>
                                        <div class="form-text text-muted small mt-1">
                                            <i class="fas fa-info-circle me-1"></i>Dapatkan kode registrasi ini dari
                                            Admin pusat terkait.
                                        </div>
                                        <input-error :message="form.errors.branch_code" />
                                    </div>
                                </div>

                                <div class="col-12" v-if="form.id_branch">
                                    <hr class="my-2 text-muted opacity-25">
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-1">
                                        <input-label labelClass="mb-2" for="password_input" value="Kata sandi" />
                                        <div class="position-relative">
                                            <i class="fas fa-lock input-icon-left"></i>
                                            <input-text :error="form.errors.password" placeholder="Minimal 8 karakter"
                                                :type="showPassword ? 'text' : 'password'"
                                                :inputClass="['input-fixed-height', 'has-icon-right', 'input-height-1']"
                                                autocomplete="off" v-model="form.password" name="password" />
                                            <i @click="togglePasswordVisibilty"
                                                :class="['input-icon-right', 'cursor-pointer', showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill']"></i>
                                        </div>
                                        <input-error :message="form.errors.password" />
                                    </div>

                                    <div v-if="passwordStrength.label" class="mt-2">
                                        <div class="progress mb-2" style="height: 6px; width: 50%;">
                                            <div :class="`progress-bar ${passwordStrength.color}`" :style="{
                                                width:
                                                    passwordStrength.width +
                                                    '%'
                                            }"></div>
                                        </div>

                                        <small class="text-muted fw-semibold">
                                            Kata sandi:
                                            {{ passwordStrength.label }}
                                        </small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-1">
                                        <input-label labelClass="mb-2" for="password_confirmation"
                                            value="Ulangi kata sandi" />
                                        <div class="position-relative">
                                            <i class="fas fa-lock input-icon-left"></i>
                                            <input-text :error="form.errors.password_confirmation"
                                                placeholder="Ulangi Kata Sandi"
                                                :type="showPasswordConfirm ? 'text' : 'password'"
                                                :inputClass="['input-fixed-height', 'has-icon-right', 'input-height-1']"
                                                autocomplete="off" v-model="form.password_confirmation"
                                                name="password_confirmation" />
                                            <i @click="togglePasswordConfirm"
                                                :class="['input-icon-right', 'cursor-pointer', showPasswordConfirm ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                                        </div>
                                        <input-error :message="form.errors.password_confirmation" />
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3 d-grid mt-4">
                                <base-button waiting="Memverifikasi Data..." :loading="form.processing"
                                    buttonClass="bg-gradient border btn-height-1" type="submit" name="submit"
                                    label="Daftar Sekarang" />
                            </div>
                        </form-wrapper>
                        <div class="mb-2 mt-3 d-flex justify-content-center align-items-center px-2 gap-1 small">
                            <span class="text-muted">Sudah punya akun karyawan?</span>
                            <Link :href="route('login')">Masuk di sini
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
