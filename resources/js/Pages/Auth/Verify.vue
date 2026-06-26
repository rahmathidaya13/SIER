<script setup>
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
const props = defineProps({
    email: {
        type: String,
        required: true
    }
});
const form = useForm();
const submit = () => {
    form.post(route("verification.send"));
};
const page = usePage();
const message = computed(() => page.props.flash.message);
</script>

<template>

    <Head title="Verify Email" />

    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-xl-8 col-12">
                <div class="card shadow-sm rounded-3 rounded overflow-hidden">
                    <div class="card-header text-bg-primary">
                        <h1 class="card-title text-center h4 mb-0 p-2 gap-2"> <i class="fas fa-envelope"></i>
                            Verifikasi email</h1>
                    </div>
                    <div class="card-body p-4">
                        <alert :duration="15" :variant="$page.props.flash.error ? 'danger' : 'success'"
                            :message="$page.props.flash.error || message" />

                        <div class="text-center text-bg-light border-secondary border p-3 mb-3">
                            <span class="text-muted">
                                Tautan verifikasi telah dikirim ke email anda.
                            </span>
                            <p class="text-primary mb-0">{{ props.email }}</p>
                        </div>
                        <form-wrapper @submit="submit">
                            <div class="d-grid">
                                <base-button type="submit" label="Kirim Ulang Email Verifikasi" waiting="Memproses..."
                                    :loading="form.processing" class="bg-gradient" />
                            </div>
                        </form-wrapper>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
