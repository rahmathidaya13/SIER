<script setup>
import { computed, ref } from "vue";
import { Head, useForm, usePage } from '@inertiajs/vue3';
const page = usePage();
const message = computed(() => {
    return page.props.flash.message || page.props.flash.error
});

const form = useForm({
    password: '',
})
const submit = () => {
    form.post(route("confirm.password.send"), {
        onFinish: () => form.reset("password")
    });
};
const showPassword = ref(false)
const togglePasswordVisibilty = () => {
    showPassword.value = !showPassword.value
}
</script>
<template>

    <Head title="Konfirmasi Kata Sandi" />
    <app-layout>
        <template #content>
            <bread-crumbs :home="false" icon="fas fa-key" title="Verifikasi" :items="[{ text: 'Konfirmasi kata sandi' }]" />

            <alert :variant="page.props.flash.message ? 'success' : 'danger'" :duration="10" :message="message" />

            <div class="row justify-content-center py-5">
                <div class="col-xl-6 col-12">
                    <div class="card shadow-sm rounded-3 rounded overflow-hidden">
                        <div class="card-header text-bg-success">
                            <h1 class="card-title text-center h4 mb-0 p-2 gap-2"> <i class="fas fa-lock"></i> Konfirmasi
                                Kata
                                Sandi</h1>
                        </div>
                        <div class="card-body p-4">
                            <form-wrapper @submit="submit">
                                <div class="mb-3">
                                    <input-label class="fw-bold" for="password" value="Konfirmasi kata sandi" />
                                    <div class="position-relative">
                                        <i class="fas fa-lock input-icon-left"></i>
                                        <text-input :type="showPassword ? 'text' : 'password'" placeholder="*********"
                                            :class="['input-fixed-height', 'input-height-2', 'has-icon-right']"
                                            autocomplete="off" v-model="form.password" name="password" />
                                        <i @click="togglePasswordVisibilty"
                                            :class="['input-icon-right', 'cursor-pointer', showPassword ? 'fas fa-eye-slash' : 'fas fa-eye']"></i>
                                    </div>
                                    <input-error :message="form.errors.password" />
                                </div>

                                <div class="d-grid mb-3">
                                    <base-button icon="fas fa-paper-plane" type="submit" label="Konfirmasi"
                                        waiting="Memproses..." :loading="form.processing" variant="success"
                                        class="bg-gradient btn-height-1" />
                                </div>
                            </form-wrapper>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </app-layout>
</template>
