<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { hasRole } from "@/Composables/useAuth";
import { currencyFormat } from "@/Helpers/currencyFormat";
import axios from "axios";
const props = defineProps({
    idSales: {
        type: String,
        default: null,
    },
    show: Boolean,
});

const dataSales = ref(null);
const isLoading = ref(false);
const emit = defineEmits(["update:show", "saved"]);
const close = () => {
    emit("update:show", false);
    resetForm();
    dataSales.value = null;
};

const form = useForm({
    cancelled_reason: "",
    cancelled_notes: "",
});
const fillForm = (data) => {
    form.defaults({
        cancelled_reason: data.cancelled_reason || "",
        cancelled_notes: data.cancelled_notes || "",
    });

    dataSales.value = data;
    form.reset();
};
const resetForm = () => {
    form.reset();
    form.clearErrors();
};
const loadSales = async () => {
    if (!props.idSales) {
        resetForm();
        return;
    }
    isLoading.value = true;
    try {
        const { data } = await axios.get(`/sales/show/${props.idSales}`);
        fillForm(data);
        console.log(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    } finally {
        isLoading.value = false
    }
};
const submit = () => {
    form.post(`/sales/cancel/${props.idSales}`, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            emit("saved");
            close();
        },
    });
};
watch(
    () => props.show,
    async (isOpen) => {
        if (!isOpen) return;
        if (props.idSales) {
            await loadSales();
        } else {
            resetForm();
        }
    }
);
const reasonOptions = computed(() => {
    const options = [
        {
            label: "Belum ada pilihan",
            value: "",
        },
        {
            label: "Pelanggan membatalkan pesanan",
            value: "CUSTOMER_CANCELLED",
        },
        {
            label: "Pembayaran tidak diselesaikan",
            value: "PAYMENT_FAILED",
        },
        {
            label: "Pesanan tidak dapat dipenuhi",
            value: "ORDER_UNFULFILLABLE",
        },
        {
            label: "Kesalahan input transaksi",
            value: "DATA_ERROR",
        },
        {
            label: "Duplikasi transaksi",
            value: "DUPLICATE",
        },
        {
            label: "Lainnya",
            value: "OTHER",
        },
    ];

    // if (hasRole('admin')) {
    //     options.push({
    //         label: 'Dibatalkan oleh admin',
    //         value: 'ADMIN_CANCELLED'
    //     });
    // }

    return options;
});
</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="600px" size="modal-lg" :icon="['text-danger', 'bi bi-x-octagon']" :show="props.show"
                title="Pembatalan Pembayaran" :mobileFullscreen="false" @closed="close">
                <template #body>
                    <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

                    <div v-if="isLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-3 small fw-medium">Sedang mempersiapkan form...</p>
                    </div>
                    <div v-else>
                        <form-wrapper @submit.prevent="submit">
                            <div class="card bg-c-slate text-white border-0 mb-4 overflow-hidden position-relative">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <p class="mb-1 text-white-50 small text-uppercase fw-bold ls-1">
                                                {{ dataSales?.invoice }}
                                            </p>

                                            <h5 class="mb-0 fw-bold text-capitalize">
                                                {{ dataSales?.lead?.name }}
                                            </h5>
                                        </div>

                                        <div class="text-end">
                                            <span class="badge bg-light text-dark">
                                                {{ dataSales?.payment_status }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row mt-4 pt-3 border-top border-light border-opacity-50">
                                        <div class="col-4">
                                            <p class="mb-0 text-white-50 small">Total Deal</p>

                                            <h6 class="mb-0 fw-bold">
                                                {{ currencyFormat(dataSales?.deal_price) }}
                                            </h6>
                                        </div>

                                        <div class="col-4 text-center">
                                            <p class="mb-0 text-white-50 small">Dibayar</p>

                                            <h6 class="mb-0 fw-bold">
                                                {{ currencyFormat(dataSales?.paid_amount) }}
                                            </h6>
                                        </div>

                                        <div class="col-4 text-end">
                                            <p class="mb-0 text-white-50 small">Sisa Tagihan</p>

                                            <h6 class="mb-0 fw-bold text-info">
                                                {{ currencyFormat(dataSales?.remaining_bill) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert border alert-warning d-flex align-items-start gap-2 mb-4">
                                <i class="bi bi-exclamation-triangle-fill"></i>

                                <div class="small">
                                    <strong>Perhatian!</strong>
                                    <div class="mt-1">
                                        Pembatalan transaksi akan mengubah status transaksi menjadi
                                        <strong>CANCELLED</strong>
                                        dan tercatat sebagai riwayat pembatalan.
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 mb-3">
                                    <input-label labelClass="small text-muted mb-2">
                                        ALASAN PEMBATALAN
                                        <span class="text-danger">*</span>
                                    </input-label>

                                    <select-input text="Pilih Alasan Pembatalan" name="cancelled_reason"
                                        v-model="form.cancelled_reason" :error="form.errors.cancelled_reason"
                                        :options="reasonOptions" />
                                    <input-error :message="form.errors.cancelled_reason" />
                                </div>

                                <div class="col-12 mb-3">
                                    <input-label labelClass="small text-muted mb-2">
                                        CATATAN PEMBATALAN
                                        <span v-if="form.cancelled_reason === 'OTHER'" class="text-danger">
                                            *
                                        </span>
                                    </input-label>

                                    <text-area v-model="form.cancelled_notes" :rows="4"
                                        :error="form.errors.cancelled_notes" :max-char="1000"
                                        placeholder="Jelaskan alasan atau informasi tambahan terkait pembatalan transaksi..." />
                                    <input-error :message="form.errors.cancelled_notes" />

                                </div>
                            </div>
                        </form-wrapper>
                    </div>
                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4 btn-height-1"
                            @click.prevent="close">
                            Batal
                        </button>

                        <button @click="submit" type="button" :disabled="form.processing || isLoading"
                            class="btn btn-danger fw-bold shadow-sm btn-height-1">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="bi bi-x-circle me-1"></i> BATALKAN PEMBAYARAN
                        </button>
                    </div>
                </template>
            </modal>
        </div>
    </div>
</template>
