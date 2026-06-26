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
    id_channel: null,
    id_product: '',
    temperature: 'WARM',
    status: 'NEW',
    follow_up_date: '',
    notes: '',
    product_name: '',
    new_activity: ''
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
const selectedProduct = {
    width: '100%',
    placeholder: 'Ketik nama produk...',
    allowClear: true,
    minimumInputLength: 2,
    ajax: {
        url: route('product.search'), // Arahkan ke endpoint controller Anda
        dataType: 'json',
        delay: 300, // Tunggu 300ms setelah user berhenti mengetik baru request ke backend (Debounce)
        data: function (params) {
            return {
                keyword: params.term // Kata kunci yang diketik user dikirim sebagai ?keyword=...
            };
        },
        processResults: function (data) {
            // Mapping respon backend menjadi format {id, text} yang dipahami Select2
            const rawArray = Array.isArray(data) ? data : (data.data || []);
            return {
                results: rawArray.map(item => ({
                    id: item.id_product,
                    text: item.name,
                    product_name: item.name
                }))
            };
        }
    }
}
const onProductSelected = (selectedOption) => {
    if (selectedOption && selectedOption.product_name) {
        form.product_name = selectedOption.product_name;
    } else {
        form.product_name = '';
    }
}
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
console.log(props);
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
                form.product_name = props.leads?.product?.product_name || '';
                form.new_activity = props.leads?.activity?.description || '';
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
                                <label for="name_input" class="form-label small fw-bold text-muted">NAMA PROSPEK
                                    *</label>
                                <input-text :error="form.errors.name" v-model="form.name" name="name"
                                    placeholder="Cth: Budi Santoso" />
                                <input-error :message="form.errors.name" />
                            </div>

                            <div class="col-md-6">
                                <label for="phone_input" class="form-label small fw-bold text-muted">NO. WHATSAPP
                                    *</label>
                                <input-number :maxChar="15" :error="form.errors.phone" v-model="form.phone" name="phone"
                                    placeholder="081233445566" />
                                <input-error :message="form.errors.phone" />
                            </div>

                            <div class="col-md-6">
                                <label for="channels_select" class="form-label small fw-bold text-muted">SUMBER TRAFIK
                                    (CHANNEL) *</label>
                                <select-input text="-- Pilih Channel --" name="channels" v-model="form.id_channel"
                                    :error="form.errors.id_channel" :options="channelOptions" />
                                <input-error :message="form.errors.id_channel" />
                            </div>

                            <div class="col-md-6">
                                <label for="product_select" class="form-label small fw-bold text-muted">PRODUK YANG
                                    DIMINATI</label>
                                <select-2 @select="onProductSelected" :settings="selectedProduct" name="product"
                                    v-model="form.id_product" />
                                <input-error :message="form.errors.id_product" />
                            </div>

                            <div class="col-md-4">
                                <label for="temp_select" class="form-label small fw-bold text-muted">SUHU
                                    KETERTARIKAN</label>
                                <select-input text="-- Pilih Suhu --" name="temp" v-model="form.temperature"
                                    :error="form.errors.temperature"
                                    :selectClass="{ 'text-danger': form.temperature === 'COLD', 'text-warning': form.temperature === 'WARM', 'text-success': form.temperature === 'HOT' }"
                                    :options="[
                                        { label: '🔥 HOT (Siap Bayar)', value: 'HOT' },
                                        { label: '⏳ WARM (Tanya-tanya)', value: 'WARM' },
                                        { label: '❄️ COLD (Sekedar Lewat)', value: 'COLD' },
                                    ]" />
                                <input-error :message="form.errors.temperature" />
                            </div>

                            <div class="col-md-4">
                                <label for="status_select" class="form-label small fw-bold text-muted">STATUS
                                    PROSPEK</label>
                                <select-input text="-- Pilih Status --" v-model="form.status" name="status"
                                    :error="form.errors.status" :disabled="!form.status === 'CLOSED_WON'" :options="[
                                        { label: '⭐ Baru Masuk', value: 'NEW' },
                                        { label: '💬 Sedang Follow Up', value: 'FOLLOW_UP' },
                                        { label: '✖ Lost / Batal', value: 'CLOSED_LOST' },
                                        { label: '✔ Closing (Terjual)', value: 'CLOSED_WON' },
                                    ]" />
                                <input-error :message="form.errors.status" />
                            </div>

                            <div class="col-md-4">
                                <label for="follow_up_date_input" class="form-label small fw-bold text-muted">JADWAL
                                    FOLLOW UP</label>
                                <input-text name="follow_up_date" v-model="form.follow_up_date" type="date" />
                            </div>

                            <div class="col-12">
                                <label for="notes_input" class="form-label small fw-bold text-muted">CATATAN / RIWAYAT
                                    CHAT</label>
                                <text-area :error="form.errors.new_activity" name="notes" v-model="form.new_activity"
                                    :rows="5" placeholder="Tuliskan kendala atau permintaan khusus dari klien..." />
                                <input-error :message="form.errors.new_activity" />


                                <div v-if="props.leads?.activity && props.leads?.activity.length > 0" class="mt-4 pt-2">
                                    <label class="form-label small fw-bold text-muted mb-3 d-block"
                                        style="letter-spacing: 0.5px;">
                                        🕒 JEJAK REKAM KONSUMEN (ACTIVITY TIMELINE):
                                    </label>

                                    <div class="ps-1 pe-2" style="max-height: 240px; overflow-y: auto;">
                                        <div class="d-flex gap-3 mb-3 position-relative" v-for="act in leads.activity"
                                            :key="act.id_lead_activity">

                                            <div class="timeline-line"></div>

                                            <div class="badge bg-primary-subtle text-primary rounded-circle p-0 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm"
                                                style="width: 34px; height: 34px; z-index: 2;">
                                                <i class="bi bi-person-fill fs-6"></i>
                                            </div>

                                            <div class="bg-light p-2 px-3 rounded-3 border flex-grow-1 shadow-sm">
                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-1 border-bottom pb-1">
                                                    <span class="fw-bold text-dark" style="font-size: 12px;">
                                                        {{ act.user?.activity_creator_name || 'Sistem' }}
                                                    </span>
                                                    <span class="text-muted" style="font-size: 10px;">
                                                        {{ new Date(act.created_at).toLocaleString('id-ID',
                                                            {
                                                                day: 'numeric', month: 'short', hour: '2-digit',
                                                                minute: '2-digit'
                                                            }) }}
                                                    </span>
                                                </div>
                                                <p class="mb-0 text-slate-700 mt-1"
                                                    style="font-size: 12.5px; white-space: pre-line;">{{ act.description
                                                    }}</p>
                                            </div>

                                        </div>
                                    </div>
                                </div>
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
<style scoped>
.timeline-line {
    position: absolute;
    left: 16px;
    top: 34px;
    bottom: -18px;
    width: 2px;
    background-color: #cbd5e1;
    z-index: 1;
}
</style>
