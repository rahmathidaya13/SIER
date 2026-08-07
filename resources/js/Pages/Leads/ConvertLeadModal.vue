<script setup>
import { watch, computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { currencyFormat } from "@/Helpers/currencyFormat";
import axios from "axios";
const props = defineProps({
    show: Boolean,
    idLead: {
        type: String,
        default: null
    },
});

const emit = defineEmits(["update:show"]);
const isLoading = ref(false);
const form = useForm({
    deal_price: 0,
    paid_amount: 0,
    payment_status: 'LUNAS',
    payment_method: 'cash',
    quantity: '',
    bank_name: '',
    notes: ''
});

// Otomatisasi UX: Pantau perubahan Harga Deal & Status Lunas
watch(
    () => [form.payment_status, form.deal_price],
    ([newStatus, newDealPrice]) => {
        if (newStatus === 'LUNAS') {
            // Kalau Lunas, uang yang dibayar WAJIB sama dengan harga deal
            form.paid_amount = newDealPrice;
        } else if (newStatus === 'DP' && form.paid_amount === newDealPrice) {
            // Kalau pindah ke DP, kosongkan nominal bayar biar Sales ngetik sendiri
            form.paid_amount = 0;
        }
    }
);

// Persiapan Data saat Modal Terbuka
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen && props.idLead) {
            loadLead();
            form.clearErrors();
        }
    }
);

const close = async () => {
    form.reset();
    form.clearErrors();
    emit("update:show", false);
};
const submit = () => {
    const url = route('leads.convert', props.idLead);
    form.post(url, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            close();
            form.reset();
        },
    });
};

const dataLead = ref(null);

const loadLead = async () => {
    isLoading.value = true
    try {
        const { data } = await axios.get(`/leads/show/${props.idLead}`);
        const cleanData = data.data;
        dataLead.value = cleanData;
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    } finally {
        isLoading.value = false
    }

}
const dpAmount = computed(() => {
    if (!form.deal_price) return 0
    if (form.payment_status === 'DP') {
        return Number(form.deal_price * 0.5)
    }
    return form.deal_price
})
</script>

<template>
    <div v-if="props.show">
        <modal width="700px" :icon="['text-success', 'bi bi-bag-check-fill']" :show="props.show"
            title="Sahkan Closing Transaksi" @closed="close" :mobileFullscreen="false">

            <template #body>
                <alert-error-all :errors="form.errors" @close="form.clearErrors()" />
                <div v-if="isLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3 small fw-medium">Sedang mengambil data...</p>
                </div>
                <div v-else>
                    <form-wrapper @submit.prevent="submit">

                        <div class="bg-primary-subtle p-3 rounded-3 mb-3 border border-primary-subtle">

                            <div class="d-flex align-items-center gap-3">
                                <div class="fs-1 text-primary">
                                    <i class="bi bi-person-bounding-box"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark fs-5">{{ dataLead?.name }}</h6>
                                    <div class="text-primary fw-medium small">
                                        <i class="bi bi-cart-check-fill me-1"></i>
                                        Closing <strong>{{ dataLead?.products?.length || 0 }}</strong> Produk
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2 pt-2 border-top border-primary border-opacity-25">
                                <label class="form-label small fw-bold text-primary mb-2 opacity-75">
                                    PRODUK YANG DIBELI:
                                </label>
                            </div>

                            <div v-if="dataLead?.products && dataLead.products.length > 0"
                                class="d-flex flex-wrap gap-2">
                                <div v-for="(prod, index) in dataLead.products" :key="prod.id_product || index"
                                    class="badge bg-white text-dark border border-primary-subtle shadow-sm d-flex align-items-center p-2 rounded-pill"
                                    style="font-size: 12px;">

                                    <i class="bi bi-box-seam text-primary me-2 fs-6"></i>

                                    <span class="text-truncate" style="max-width: 200px;" :title="prod.product_name">
                                        {{ prod.product_name || 'Produk Tidak Diketahui' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else
                                class="text-danger small fst-italic bg-danger-subtle p-2 rounded-2 border border-danger-subtle">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Perhatian: Belum ada produk yang
                                dikaitkan ke Lead ini.
                            </div>

                        </div>

                        <div class="row g-2">
                            <div class="col-md-4">
                                <label for="deal_price_input" class="form-label small fw-bold text-muted">HARGA
                                    DEAL <span class="text-danger">*</span></label>
                                <input-currency inputClass="text-success" :decimals="0" name="deal_price"
                                    :error="form.errors.deal_price" v-model="form.deal_price" placeholder="0" />
                                <input-error :message="form.errors.deal_price" />
                                <small class="text-muted d-block mt-1">Terbilang: <strong>{{
                                    currencyFormat(form.deal_price)
                                        }}</strong></small>
                            </div>

                            <div class="col-md-4">
                                <label for="paid_amount_input" class="form-label small fw-bold text-muted">UANG MASUK
                                    SEKARANG <span class="text-danger">*</span></label>
                                <input-currency :readonly="form.payment_status === 'LUNAS'"
                                    :error="form.errors.paid_amount" :decimals="0" name="paid_amount"
                                    v-model="form.paid_amount" placeholder="0" />
                                <input-error :message="form.errors.paid_amount" />
                                <small v-if="form.deal_price - form.paid_amount > 0"
                                    class="text-danger mt-1 d-block fw-medium" style="font-size: 11px;">
                                    Sisa Tagihan: {{ currencyFormat(dpAmount) }}
                                </small>
                            </div>
                            <div class="col-md-4">
                                <label for="quantity_input" class="form-label small fw-bold text-muted">QUANTITY
                                    <span class="text-danger">*</span></label>
                                <input-number :maxChar="5" :error="form.errors.quantity" name="quantity"
                                    v-model="form.quantity" placeholder="0" />
                                <input-error :message="form.errors.quantity" />
                            </div>

                            <div class="col-md-6">
                                <label for="payment_status_select" class="form-label small fw-bold text-muted">STATUS
                                    BAYAR
                                    <span class="text-danger">*</span></label>
                                <select-input name="payment_status" v-model="form.payment_status"
                                    :error="form.errors.payment_status" :options="[
                                        { label: '✅ LUNAS Penuh', value: 'LUNAS' },
                                        { label: '⏳ Uang Muka (DP)', value: 'DP' },
                                    ]" />
                                <input-error :message="form.errors.payment_status" />
                            </div>

                            <div class="col-md-6">
                                <label for="payment_method_select" class="form-label small fw-bold text-muted">METODE
                                    PEMBAYARAN <span class="text-danger">*</span></label>
                                <select-input name="payment_method" v-model="form.payment_method"
                                    :error="form.errors.payment_method" :options="[
                                        { label: '🏦 Tunai / Cash', value: 'cash' },
                                        { label: '🏦 Transfer', value: 'transfer' },
                                        { label: '📱 Debit', value: 'debit' },
                                        { label: '💵 QRIS', value: 'qris' }
                                    ]" />
                                <input-error :message="form.errors.payment_method" />
                            </div>

                            <div v-if="form.payment_method === 'transfer' || form.payment_method === 'debit'"
                                class="col-12">
                                <label class="form-label small fw-bold text-muted mb-2">NAMA BANK<span
                                        class="text-danger">*</span></label>
                                <input-text name="bank_name" v-model="form.bank_name" :error="form.errors.bank_name"
                                    placeholder="Cth: BCA, Mandiri, BNI Dll" />
                                <input-error :message="form.errors.bank_name" />
                            </div>

                            <div class="col-12 mt-3 border-top pt-3">
                                <label for="notes_input" class="form-label small fw-bold text-muted">CATATAN TRANSAKSI
                                    (Opsional)</label>
                                <text-area :maxChar="500" :error="form.errors.notes" name="notes" v-model="form.notes"
                                    :rows="2"
                                    placeholder="Cth: Pelanggan minta jika pembelian barang banyak ada pengurangan harga..." />
                                <input-error :message="form.errors.notes" />
                            </div>
                        </div>
                    </form-wrapper>
                </div>
            </template>

            <template #footer>
                <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                    <button type="button" class="btn btn-c-slate fw-medium px-4 border" @click="close">Batal</button>
                    <button @click="submit" type="button" :disabled="form.processing || isLoading"
                        class="btn btn-c-success  fw-bold px-4 shadow-sm">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-rocket-takeoff-fill me-2"></i>
                        PROSES & SAHKAN CLOSING
                    </button>
                </div>
            </template>
        </modal>
    </div>
</template>
