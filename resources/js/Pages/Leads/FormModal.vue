<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
const props = defineProps({
    leads: Object,
    show: Boolean,
    channels: Array,
    products: Array
});


const isEdit = computed(() => !!props.leads?.id_lead);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    id_channel: '',
    id_product: '',
    temperature: 'WARM',
    status: 'NEW',
    follow_up_date: '',
    notes: ''
});

const channelOptions = computed(() => [
    ...props.channels.map(data => ({
        label: data.name,
        value: data.id_channel,
    }))
]);
const productOptions = computed(() => [
    ...props.products.map(data => ({
        label: data.name,
        value: data.id_product,
    }))
]);

const submit = () => {
    const url = isEdit.value
        ? route('leads.update', props.data?.id_lead)
        : route('leads.store');

    // Jika Edit, Laravel butuh trik method spoofing (_method: put) untuk upload file
    const transformData = isEdit.value
        ? (data) => ({ ...data, _method: 'put' })
        : (data) => data;

    form.transform(transformData).post(url, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            close();
            form.reset();
        },
        onError: () => {
            form.password = '';
        }
    });
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            // Modal sedang DIBUKA
            if (isEdit.value) {
                form.name = props.leads.name;
                form.phone = props.leads.phone;
                form.email = props.leads.email || '';
                form.id_channel = props.leads.id_channel;
                form.id_product = props.leads.id_product || '';
                form.temperature = props.leads.temperature;
                form.status = props.leads.status;
                form.follow_up_date = props.leads.follow_up_date ? props.leads.follow_up_date.substring(0, 10) : '';
                form.notes = props.leads.notes || '';
            } else {
                form.reset();
                form.clearErrors();
                form.temperature = 'WARM';
                form.status = 'NEW';
            }
        }
    }, {
    immediate: true
}
);
const emit = defineEmits(["update:show"]);
const close = () => {
    emit("update:show", false);
    form.clearErrors();
    form.reset();
}
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return isEdit.value ? 'Menyimpan...' : 'Simpan Prospek';
});

</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="750px" size="modal-lg" :icon="[
                'text-coffee-main',
                {
                    'bi bi-pencil-square text-primary': isEdit,
                    'bi bi-plus-circle-fill text-primary': !isEdit
                }
            ]" :show="props.show" :title="isEdit ? 'Edit Data Prospek' : ' Input Prospek Baru'"
                :mobileFullscreen="false" @closed="close">

                <template #body>

                    <div v-if="form.errors.unauthorized_action"
                        class="alert alert-c-danger d-flex justify-content-between align-items-center" role="alert">
                        <div class="d-flex align-items-center gap-2 fw-semibold">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ form.errors.unauthorized_action }}
                        </div>
                        <button @click="form.clearErrors('unauthorized_action')" type="button"
                            class="bg-transparent rounded-circle border-0 fs-4">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <form-wrapper @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">NAMA PROSPEK *</label>
                                <input v-model="form.name" type="text" class="form-control"
                                    placeholder="Cth: Budi Santoso" required>
                                <div v-if="form.errors.name" class="text-danger small mt-1">{{ form.errors.name }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">NO. WHATSAPP *</label>
                                <input v-model="form.phone" type="text" class="form-control" placeholder="081233445566"
                                    required>
                                <div v-if="form.errors.phone" class="text-danger small mt-1">{{ form.errors.phone }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">SUMBER TRAFIK (CHANNEL) *</label>
                                <select v-model="form.channel_id" class="form-select fw-medium" required>
                                    <option value="" disabled>-- Pilih Sumber --</option>
                                    <option v-for="c in channels" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">PRODUK YANG DIMINATI</label>
                                <select v-model="form.product_id" class="form-select">
                                    <option value="">-- Belum Menentukan --</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">SUHU KETERTARIKAN</label>
                                <select v-model="form.temperature" class="form-select fw-bold"
                                    :class="{ 'text-danger': form.temperature === 'COLD', 'text-warning': form.temperature === 'WARM', 'text-success': form.temperature === 'HOT' }">
                                    <option value="HOT">🔥 HOT (Siap Bayar)</option>
                                    <option value="WARM">⏳ WARM (Tanya-tanya)</option>
                                    <option value="COLD">❄️ COLD (Sekedar Lewat)</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">STATUS PROSPEK</label>
                                <select v-model="form.status" class="form-select fw-medium"
                                    :disabled="form.status === 'CLOSED_WON'">
                                    <option value="NEW">⭐ Baru Masuk</option>
                                    <option value="FOLLOW_UP">💬 Sedang Follow Up</option>
                                    <option value="CLOSED_LOST">✖ Lost / Batal</option>
                                    <option v-if="form.status === 'CLOSED_WON'" value="CLOSED_WON">✔ CLOSED WON</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">JADWAL FOLLOW UP</label>
                                <input v-model="form.follow_up_date" type="date" class="form-control">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">CATATAN / RIWAYAT CHAT</label>
                                <textarea v-model="form.notes" class="form-control" rows="3"
                                    placeholder="Tuliskan kendala atau permintaan khusus dari klien..."></textarea>
                            </div>
                        </div>

                    </form-wrapper>

                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4"
                            @click.prevent="close">Batal</button>
                        <button @click.prevent="submit" type="button" :disabled="form.processing"
                            class="btn btn-height-1 fw-semibold" :class="isEdit ? 'btn-c-success' : 'btn-c-primary'">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else :class="isEdit ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"></i>
                            {{ submitTextButton }}
                        </button>
                    </div>
                </template>

            </modal>
        </div>
    </div>
</template>
