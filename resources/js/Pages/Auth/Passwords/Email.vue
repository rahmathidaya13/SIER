<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    email: "",
});
const submit = () => {
    form.post(route("forgot.store"), {
        onFinish: () => form.reset("email")
    });
};
const page = usePage();
const message = computed(() => page.props.flash.message);
</script>

<template>

    <Head title="Forgot Password" />

    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-xl-6">

                <div class="card shadow-sm rounded-3 rounded overflow-hidden">
                    <div class="card-header text-bg-primary">
                        <h1 class="card-title text-center h4 mb-0 p-3 gap-2"> <i class="fas fa-lock"></i>
                            Lupa kata
                            sandi?</h1>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted mb-3">Untuk mengatur ulang kata sandi anda masukan email yang telah
                            didaftarkan
                        </p>
                        <hr />
                        <alert :duration="15" :variant="$page.props.flash.error ? 'danger' : 'success'"
                            :message="$page.props.flash.error || message" />

                        <form-wrapper @submit="submit">
                            <div class="mb-3">
                                <input-label for="email" value="Alamat Email" />
                                <div class="position-relative">
                                    <i class="fas fa-envelope input-icon-left"></i>
                                    <text-input autofocus class="input-fixed-height input-height-2"
                                        placeholder="nama@email.com" name="email" v-model="form.email" />
                                </div>
                                <input-error :message="form.errors.email" />
                            </div>

                            <div class="d-grid">
                                <base-button type="submit" label="Kirim Link Reset Password" waiting="Memproses..."
                                    :loading="form.processing" class="bg-gradient" />
                            </div>
                        </form-wrapper>
                    </div>
                    <div class="card-footer">
                        <div class="text-center p-2">
                            <Link :href="route('login')" class="text-decoration-none">
                            &larr; Kembali ke halaman masuk
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
