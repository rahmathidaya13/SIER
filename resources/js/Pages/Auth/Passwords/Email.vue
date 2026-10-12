<script setup>
import { useForm, Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const form = useForm({
    email: "",
});
const submit = () => {
    form.post(route("forgot.store"), {
        preserveScroll: true,
        onSuccess: () => form.reset("email"),
    });
};
const page = usePage();
const message = computed(() => page.props.flash.message);
const emailInput = ref(null);
onMounted(() => {
    emailInput.value.focus();
});

</script>

<template>

    <Head title="Lupa Kata Sandi" />

    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-xl-6 col-12">

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
                        <callout :icon="false" :duration="0" />
                        <form-wrapper @submit="submit">
                            <div class="mb-3">
                                <input-label for="email_input" value="Alamat Email" labelClass="mb-2" />
                                <div class="position-relative">
                                    <i class="fas fa-envelope input-icon-left"></i>
                                    <input-text ref="emailInput" :error="form.errors.email"
                                        inputClass="input-fixed-height" placeholder="nama@email.com" name="email"
                                        v-model="form.email" />
                                </div>
                                <input-error :message="form.errors.email" />
                            </div>

                            <div class="d-grid">
                                <button type="submit"
                                    class="btn btn-primary bg-gradient py-2 shadow-sm d-flex justify-content-center align-items-center"
                                    :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"
                                        role="status"></span>
                                    <i v-else class="fas fa-paper-plane me-2"></i> Kirim Link
                                </button>
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
