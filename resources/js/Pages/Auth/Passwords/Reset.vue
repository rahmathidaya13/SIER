<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue';

const props = defineProps({ token: String, email: String })

const page = usePage();
const message = computed(() => page.props.flash.message);
const showPassword = ref(false)
const showPasswordConfirm = ref(false)

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
})
const submit = () => {
    form.post(route("password.store"), {}, {
        preserveScroll: true,
        replace: true,
        onSuccess: () => form.reset("password", "password_confirmation"),
    });
};


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

const togglePasswordVisibilty = () => {
    showPassword.value = !showPassword.value
}
const togglePasswordConfirm = () => {
    showPasswordConfirm.value = !showPasswordConfirm.value
}
</script>

<template>

    <Head title="Atur Ulang Kata Sandi" />

    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-xl-6 col-12">
                <div class="card shadow-sm rounded-3 rounded overflow-hidden">
                    <div class="card-header text-bg-primary">
                        <h1 class="card-title text-center h4 mb-0 p-3 gap-2"> <i class="fas fa-unlock"></i> Atur Ulang
                            Kata
                            Sandi</h1>
                    </div>
                    <div class="card-body p-4">
                        <callout :icon="false" :duration="0" />

                        <form-wrapper @submit="submit">
                            <input type="hidden" v-model="form.token" />
                            <div class="mb-3">
                                <input-label labelClass="mb-2" for="email_input" value="Alamat Email" />
                                <div class="position-relative">
                                    <i class="bi bi-envelope-fill input-icon-left"></i>
                                    <input-text :error="form.errors.email" type="email" inputClass="input-fixed-height"
                                        readonly placeholder="nama@email.com" name="email" v-model="form.email" />
                                </div>
                                <input-error :message="form.errors.email" />
                            </div>
                            <div class="mb-3">
                                <input-label labelClass="mb-2" for="password" value="Kata sandi baru" />
                                <div class="position-relative">
                                    <i class="bi bi-lock-fill input-icon-left"></i>
                                    <input-text :error="form.errors.password" :type="showPassword ? 'text' : 'password'"
                                        placeholder="Kata Sandi" :class="['input-fixed-height', 'has-icon-right']"
                                        autocomplete="off" v-model="form.password" name="password" />
                                    <i @click="togglePasswordVisibilty"
                                        :class="['input-icon-right', 'cursor-pointer', showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill']"></i>
                                </div>
                                <input-error :message="form.errors.password" />

                                <div v-if="passwordStrength.label" class="mt-2">
                                    <div class="progress mb-2" style="height: 6px; width: 50%;">
                                        <div :class="`progress-bar ${passwordStrength.color}`" :style="{
                                            width:
                                                passwordStrength.width +
                                                '%'
                                        }"></div>
                                    </div>

                                    <small class="text-muted fw-semibold">
                                        kata sandi:
                                        {{ passwordStrength.label }}
                                    </small>
                                </div>

                            </div>

                            <div class="mb-3">
                                <input-label labelClass="mb-2" for="password_confirmation" value="Ulangi kata sandi" />
                                <div class="position-relative">
                                    <i class="bi bi-lock-fill input-icon-left"></i>
                                    <input-text :error="form.errors.password_confirmation"
                                        :type="showPasswordConfirm ? 'text' : 'password'"
                                        placeholder="Ulangi kata sandi"
                                        :class="['input-fixed-height', 'has-icon-right']" autocomplete="off"
                                        v-model="form.password_confirmation" name="password_confirmation" />
                                    <i @click="togglePasswordConfirm"
                                        :class="['input-icon-right', 'cursor-pointer', showPasswordConfirm ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill']"></i>
                                </div>
                                <input-error :message="form.errors.password_confirmation" />
                            </div>
                            <div class="d-grid mb-3">
                                <button type="submit"
                                    class="btn btn-primary bg-gradient py-2 shadow-sm d-flex justify-content-center align-items-center"
                                    :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"
                                        role="status"></span>
                                    <i v-else class="fas fa-edit me-2"></i> Perbarui kata sandi
                                </button>
                            </div>
                        </form-wrapper>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
