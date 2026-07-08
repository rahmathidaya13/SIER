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

const form = useForm({
    deal_price: 0,
    paid_amount: 0,
    payment_status: 'LUNAS',
    payment_method: 'cash',
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
    try {
        const { data } = await axios.get(`/leads/show/${props.idLead}`);
        dataLead.value = data;
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    }

}

</script>

<template>
    <div v-if="props.show">
        <modal width="700px" :icon="['text-success', 'bi bi-bag-check-fill']" :show="props.show"
            title="Sahkan Closing Transaksi" @closed="close" :mobileFullscreen="false">

            <template #body>
                <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

                <form-wrapper @submit.prevent="submit">

                    <div class="bg-primary-subtle p-3 rounded-3 mb-3 border border-primary-subtle">
                        <div class="d-flex align-items-center gap-3">
                            <div class="fs-1 text-primary"><i class="bi bi-person-bounding-box"></i></div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark fs-5">{{ dataLead?.name }}</h6>
                                <p class="mb-0 text-muted small">
                                    Produk Diminati: <strong class="text-primary">
                                        {{ dataLead?.product.product_name || 'Belum Pilih Paket' }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label for="deal_price_input" class="form-label small fw-bold text-muted">HARGA
                                DEAL*</label>
                            <input-currency inputClass="text-success" :decimals="0" name="deal_price"
                                :error="form.errors.deal_price" v-model="form.deal_price" placeholder="0" />
                            <input-error :message="form.errors.deal_price" />
                            <small class="text-muted d-block mt-1">Terbilang: <strong>{{ currencyFormat(form.deal_price)
                                    }}</strong></small>
                        </div>

                        <div class="col-md-6">
                            <label for="paid_amount_input" class="form-label small fw-bold text-muted">UANG MASUK
                                SEKARANG *</label>
                            <input-currency :readonly="form.payment_status === 'LUNAS'" :error="form.errors.paid_amount"
                                :decimals="0" name="paid_amount" v-model="form.paid_amount" placeholder="0" />
                            <input-error :message="form.errors.paid_amount" />
                            <small v-if="form.deal_price - form.paid_amount > 0"
                                class="text-danger mt-1 d-block fw-medium" style="font-size: 11px;">
                                Sisa Tagihan: {{ currencyFormat(form.deal_price - form.paid_amount) }}
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label for="payment_status_select" class="form-label small fw-bold text-muted">STATUS BAYAR
                                *</label>
                            <select-input name="payment_status" v-model="form.payment_status"
                                :error="form.errors.payment_status" :options="[
                                    { label: '✅ LUNAS Penuh', value: 'LUNAS' },
                                    { label: '⏳ Uang Muka (DP)', value: 'DP' },
                                ]" />
                            <input-error :message="form.errors.payment_status" />
                        </div>

                        <div class="col-md-6">
                            <label for="payment_method_select" class="form-label small fw-bold text-muted">METODE
                                PEMBAYARAN *</label>
                            <select-input name="payment_method" v-model="form.payment_method"
                                :error="form.errors.payment_method" :options="[
                                    { label: '🏦 Tunai / Cash', value: 'cash' },
                                    { label: '🏦 Transfer', value: 'transfer' },
                                    { label: '📱 Debit', value: 'debit' },
                                    { label: '💵 QRIS', value: 'qris' }
                                ]" />
                            <input-error :message="form.errors.payment_method" />
                        </div>

                        <div v-if="form.payment_method === 'transfer' || form.payment_method === 'debit'" class="col-12">
                            <label class="form-label small fw-bold text-muted mb-2">NAMA BANK*</label>
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
            </template>

            <template #footer>
                <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                    <button type="button" class="btn btn-c-slate fw-medium px-4 border" @click="close">Batal</button>
                    <button @click="submit" type="button" :disabled="form.processing"
                        class="btn btn-c-success fw-bold px-4 shadow-sm">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-rocket-takeoff-fill me-2"></i>
                        PROSES & SAHKAN CLOSING
                    </button>
                </div>
            </template>
        </modal>
    </div>
</template>
