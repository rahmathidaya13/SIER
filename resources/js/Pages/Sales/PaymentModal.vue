<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import { useRowAlert } from '@/Composables/useAlert';
import { currencyFormat } from "@/Helpers/currencyFormat";
const { show, __alert, hide } = useRowAlert();
import axios from "axios";
const props = defineProps({
    idSales: {
        type: String,
        default: null
    },
    show: Boolean,
});

const isEdit = computed(() => !!props.idSales);
const dataSales = ref(null);
const emit = defineEmits(["update:show", "saved"]);
const close = () => {
    emit("update:show", false);
    resetForm();
    dataSales.value = null;
}

const form = useForm({
    payment_method: null,
    bank_name: '',
    paid_amount: 0,
})
const fillForm = (data) => {
    form.defaults({
        paid_amount: data.paid_amount,
    })

    dataSales.value = data
    form.reset();
}
const resetForm = () => {
    form.reset();
    form.clearErrors();
}
const loadSales = async () => {

    if (!props.idSales) {
        resetForm();
        return
    }

    try {
        const { data } = await axios.get(`/sales/show/${props.idSales}`);
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    }
}
const submit = () => {
    form.post(`/sales/pay/${props.idSales}`, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit("saved");
            close();
        }
    })
}
watch(() => props.show, async (isOpen) => {
    if (!isOpen) return;
    if (props.idSales) {
        await loadSales();
    } else {
        resetForm();
    }
})

</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="600px" size="modal-lg" :icon="['text-success', 'bi bi-wallet2']" :show="props.show"
                title="Pelunasan Tagihan" :mobileFullscreen="false" @closed="close">

                <template #body>

                    <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

                    <form-wrapper @submit.prevent="submit">
                        <div
                            class="card bg-c-slate text-white border-0 shadow-lg mb-4 overflow-hidden position-relative">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1 text-white-50 small text-uppercase fw-bold ls-1"> {{
                                            dataSales?.invoice }}</p>
                                        <h5 class="mb-0 fw-bold text-capitalize">{{ dataSales?.lead?.name }}</h5>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark">{{ dataSales?.payment_status }}</span>
                                    </div>
                                </div>

                                <div class="row mt-4 pt-3 border-top border-light border-opacity-50">
                                    <div class="col-6">
                                        <p class="mb-0 text-white-50 small">Total Deal</p>
                                        <h6 class="mb-0 fw-bold">{{ currencyFormat(dataSales?.deal_price) }}</h6>
                                    </div>
                                    <div class="col-6 text-end">
                                        <p class="mb-0 text-white-50 small">Sisa Tagihan</p>
                                        <h5 class="mb-0 fw-bold text-info">{{
                                            currencyFormat(dataSales?.remaining_bill) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label small fw-bold text-muted mb-2">METODE PEMBAYARAN
                                    *</label>
                                <select-input text="Pilih metode pembayaran" name="payment_method"
                                    v-model="form.payment_method" :error="form.errors.payment_method" :options="[
                                        { label: '🏦 Tunai / Cash', value: 'cash' },
                                        { label: '🏦 Transfer', value: 'transfer' },
                                        { label: '📱 Debit', value: 'debit' },
                                        { label: '💵 QRIS', value: 'qris' }
                                    ]" />
                                <input-error :message="form.errors.payment_method" />
                            </div>
                            <div v-if="form.payment_method === 'transfer' || form.payment_method === 'debit'"
                                class="col-12 mb-3">
                                <label class="form-label small fw-bold text-muted mb-2">NAMA BANK*</label>
                                <input-text name="bank_name" v-model="form.bank_name" :error="form.errors.bank_name"
                                    placeholder="Cth: BCA" />
                                <input-error :message="form.errors.bank_name" />
                            </div>

                            <div class="col-12 mb-2">
                                <label class="form-label small fw-bold text-muted mb-2">NOMINAL PEMBAYARAN *</label>
                                <div class="input-group shadow-sm border-0">
                                    <input-currency :error="form.errors.paid_amount" name="paid_amount" :decimals="0"
                                        v-model="form.paid_amount" inputClass="fs-4 fw-bold text-success" />
                                </div>
                                <input-error :message="form.errors.paid_amount" />
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">Masukkan nominal yang diterima hari ini.</small>
                                    <button type="button" @click="form.paid_amount = dataSales?.remaining_bill"
                                        class="btn btn-link btn-sm p-0 text-decoration-none">
                                        Klik untuk LUNAS ({{ currencyFormat(dataSales?.remaining_bill) }})
                                    </button>
                                </div>
                            </div>


                        </div>
                    </form-wrapper>

                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">

                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4 btn-height-1"
                            @click.prevent="close">Batal</button>

                        <button @click="submit" type="button" :disabled="form.processing"
                            class="btn btn-success fw-bold shadow-sm btn-height-1">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="bi bi-check2-circle me-1"></i> SIMPAN PEMBAYARAN
                        </button>

                    </div>
                </template>

            </modal>
        </div>
    </div>
</template>
