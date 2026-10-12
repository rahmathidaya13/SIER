<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import { currencyFormat } from "@/Helpers/currencyFormat";
import axios from "axios";
import { useRequest } from "@/Composables/useRequest";
const { isLoading, requests } = useRequest();
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
    return requests(async () => {
        const { data } = await axios.get(
            route('sales.show', props.idSales)
        );
        const salesData = data.data;
        fillForm(salesData);
        console.log(salesData);
    },
        "Gagal mengambil data transaksi",
        false
    );
}
const loadInitialData = async () => {
    if (!props.idSales) {
        resetForm();
        return
    }
    isLoading.value = true
    try {
        await Promise.all([
            loadSales(),
        ])
    } catch (error) {
        console.error('Gagal memuat detail transaksi:', error);
    } finally {
        isLoading.value = false
    }
}
const submit = () => {
    form.post(`/sales/pay/${props.idSales}`, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            emit("saved");
            close();
        }
    })
}
watch(() => props.show, async (isOpen) => {
    if (!isOpen) return;
    if (props.idSales) {
        await loadInitialData();
    } else {
        resetForm();
    }
})
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return isEdit.value ? 'Simpan Perubahan' : ' Simpan';
});
</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="600px" size="modal-lg" :icon="['text-success', 'bi bi-wallet2']" :show="props.show"
                title="Pelunasan Tagihan" :mobileFullscreen="false" @closed="close">

                <template #body>

                    <alert-error-all :errors="form.errors" @close="form.clearErrors()" />


                    <div v-if="isLoading" class="d-flex flex-column align-items-center justify-content-center p-5">
                        <div class="spinner-border text-primary mb-3"></div>

                        <div class="fw-semibold">
                            Sedang mengambil data...
                        </div>

                        <small class="text-muted">
                            Mohon tunggu sebentar
                        </small>
                    </div>
                    <div v-else>
                        <div class="card border rounded-3 overflow-hidden mb-3">
                            <div
                                class="card-header bg-primary bg-opacity-10 border-bottom border-primary border-opacity-10 p-3">
                                <div
                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm"
                                            style="width: 38px; height: 38px;">
                                            <i class="bi bi-receipt fs-6"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span
                                                    class="badge bg-white text-primary border border-primary-subtle fw-semibold p-2"
                                                    style="font-size: 14px;">
                                                    {{ dataSales?.invoice || 'NO-INVOICE' }}
                                                </span>
                                            </div>
                                            <h6 class="mb-0 fw-bold text-dark mt-1 text-capitalize text-truncate"
                                                style="font-size: 18px;" :title="dataSales?.lead?.name">
                                                {{ dataSales?.lead?.name || 'Nama Pelanggan' }}
                                            </h6>
                                            <div class="d-inline-flex align-items-center gap-2">
                                                <span class="small text-muted fw-semibold" style="font-size: 12px;">
                                                    <i class="bi bi-telephone-fill me-1"></i>
                                                    {{ dataSales?.lead?.phone }}
                                                </span>
                                                <span class="small text-muted fw-semibold" style="font-size: 12px;">
                                                    <i class="bi bi-envelope-fill me-1"></i>{{ dataSales?.lead?.email ||
                                                        'Tidak ada email' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="align-self-start align-self-sm-center flex-shrink-0">
                                        <span
                                            class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning border-opacity-25 px-2 py-1 rounded-pill fw-medium"
                                            style="font-size: 12px;">
                                            <i class="bi bi-clock-history me-1"></i>
                                            {{ dataSales?.payment_status === 'LUNAS' ? 'Lunas' : 'Dana Awal (DP)' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-3 bg-white">
                                <div class="d-flex flex-column flex-sm-row gap-2">
                                    <!-- Total Deal -->
                                    <div class="flex-fill p-2 bg-light rounded-3 border">
                                        <p class="mb-1 text-muted fw-semibold text-uppercase tracking-wide"
                                            style="font-size: 12px;">
                                            Total Nilai Deal
                                        </p>
                                        <div class="fw-bold text-dark" style="font-size: 18px;">
                                            {{ currencyFormat(dataSales?.deal_price) }}
                                        </div>
                                    </div>

                                    <div
                                        class="flex-fill p-2 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <p class="mb-0 text-danger fw-semibold text-uppercase tracking-wide"
                                                style="font-size: 12px;">
                                                Sisa Tagihan Wajib Bayar
                                            </p>
                                            <i class="bi bi-exclamation-circle text-danger"
                                                style="font-size: 11px;"></i>
                                        </div>
                                        <div class="fw-bold text-danger" style="font-size: 18px;">
                                            {{ currencyFormat(dataSales?.remaining_bill) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border border-light-subtle rounded-3 p-3 p-md-3 bg-white mb-2">
                            <form-wrapper @submit.prevent="submit">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <input-label labelClass="mb-1" for="payment_method" style="font-size: 12px;">
                                            <span class="text-uppercase text-muted fw-bold">
                                                Metode Pembayaran <span class="text-danger">*</span>
                                            </span>
                                        </input-label>

                                        <select-input text="Pilih Metode Pembayaran" name="payment_method"
                                            v-model="form.payment_method" :error="form.errors.payment_method" :options="[
                                                { label: '💵 Tunai / Cash', value: 'cash' },
                                                { label: '🏦 Transfer Bank', value: 'transfer' },
                                                { label: '💳 Kartu Debit', value: 'debit' },
                                                { label: '📱 QRIS / E-Wallet', value: 'qris' }
                                            ]" />
                                        <input-error :message="form.errors.payment_method" class="mt-1" />
                                    </div>

                                    <div v-if="form.payment_method === 'transfer' || form.payment_method === 'debit'"
                                        class="col-12">
                                        <input-label labelClass="mb-1 text-uppercase" style="font-size: 12px;">
                                            Nama Bank & Rekening <span class="text-danger">*</span>
                                        </input-label>

                                        <input-text name="bank_name" v-model="form.bank_name"
                                            :error="form.errors.bank_name" placeholder="Contoh: BCA, Mandiri, BNI" />
                                        <input-error :message="form.errors.bank_name" class="mt-1" />
                                    </div>

                                    <!-- Input Nominal Pembayaran -->
                                    <div class="col-12">
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                                            <input-label labelClass="mb-1 text-uppercase" style="font-size: 12px;">
                                                Nominal Diterima <span class="text-danger">*</span>
                                            </input-label>

                                            <!-- Tombol Otomatis Lunas -->
                                            <button type="button" @click="form.paid_amount = dataSales?.remaining_bill"
                                                class="btn btn-sm btn-link text-primary p-0 text-decoration-none fw-semibold d-flex align-items-center gap-1"
                                                style="font-size: 12px;">
                                                <i class="bi bi-lightning-charge-fill text-warning"></i>
                                                Bayar Lunas ({{ currencyFormat(dataSales?.remaining_bill) }})
                                            </button>
                                        </div>

                                        <input-currency :error="form.errors.paid_amount" name="paid_amount"
                                            :decimals="0" v-model="form.paid_amount"
                                            inputClass="fs-4 fw-bold text-success bg-transparent text-start pe-2" />
                                        <input-error :message="form.errors.paid_amount" class="mt-1" />

                                        <div class="d-flex align-items-center gap-1 text-muted mt-2"
                                            style="font-size: 11px;">
                                            <i class="bi bi-info-circle"></i>
                                            <span>Pastikan nominal yang dicatat sesuai dengan mutasi fisik atau rekening
                                                koran.</span>
                                        </div>
                                    </div>
                                </div>
                            </form-wrapper>
                        </div>
                    </div>

                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">

                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4 btn-height-1"
                            @click.prevent="close">Batal</button>

                        <button @click="submit" type="button" :disabled="form.processing || isLoading"
                            class="btn btn-success fw-bold shadow-sm btn-height-1" :class="{
                                'btn-dark': form.processing || isLoading
                            }">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="bi bi-check2-circle me-1"></i> {{ submitTextButton }}
                        </button>

                    </div>
                </template>

            </modal>
        </div>
    </div>
</template>
<style scoped>
.nominal-field-wrapper:focus-within {
    border-color: #198754 !important;
    background-color: #fff !important;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.12);
}
</style>
