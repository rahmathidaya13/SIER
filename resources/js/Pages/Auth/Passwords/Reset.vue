<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue';

const props = defineProps({ token: String })

const page = usePage();
const message = computed(() => page.props.flash.message);

const form = useForm({
    token: props.token,
    email: page.props.query?.email ?? '',
    password: '',
    password_confirmation: '',
})
const submit = () => {
    form.post(route("password.store"));
};
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const togglePasswordVisibilty = () => {
    showPassword.value = !showPassword.value
}
const togglePasswordConfirm = () => {
    showPasswordConfirm.value = !showPasswordConfirm.value
}
console.log(page.props);
</script>

<template>

    <Head title="Forgot Password" />

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
                        <alert :duration="15" :variant="$page.props.flash.error ? 'danger' : 'success'"
                            :message="$page.props.flash.error || message" />
                        <form-wrapper @submit="submit">
                            <input type="hidden" v-model="form.token" />
                            <div class="mb-3">
                                <input-label class="fw-bold" for="email" value="Alamat Email" />
                                <div class="position-relative">
                                    <i class="fas fa-envelope input-icon-left"></i>
                                    <text-input type="email" class="input-fixed-height" readonly
                                        placeholder="nama@email.com" name="email" v-model="form.email" />
                                </div>
                                <input-error :message="form.errors.email" />
                            </div>
                            <div class="mb-3">
                                <input-label class="fw-bold" for="password" value="Kata sandi baru" />
                                <div class="position-relative">
                                    <i class="fas fa-lock input-icon-left"></i>
                                    <text-input :type="showPassword ? 'text' : 'password'" placeholder="Kata Sandi"
                                        :class="['input-fixed-height', 'has-icon-right']" autocomplete="off"
                                        v-model="form.password" name="password" />
                                    <i @click="togglePasswordVisibilty"
                                        :class="['input-icon-right', 'cursor-pointer', showPassword ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                                </div>
                                <input-error :message="form.errors.password" />
                            </div>

                            <div class="mb-3">
                                <input-label class="fw-bold" for="password_confirmation" value="Ulangi kata sandi" />
                                <div class="position-relative">
                                    <i class="fas fa-lock input-icon-left"></i>
                                    <text-input :type="showPasswordConfirm ? 'text' : 'password'"
                                        placeholder="Ulangi kata sandi"
                                        :class="['input-fixed-height', 'has-icon-right']" autocomplete="off"
                                        v-model="form.password_confirmation" name="password_confirmation" />
                                    <i @click="togglePasswordConfirm"
                                        :class="['input-icon-right', 'cursor-pointer', showPasswordConfirm ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                                </div>
                                <input-error :message="form.errors.password_confirmation" />
                            </div>
                            <div class="d-grid mb-3">
                                <base-button icon="fas fa-edit" type="submit" label="Perbarui kata sandi"
                                    waiting="Memproses..." :loading="form.processing" class="bg-gradient" />
                            </div>
                        </form-wrapper>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
