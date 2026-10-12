<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { hasRole } from "@/Composables/useAuth";
import { currencyFormat } from "@/Helpers/currencyFormat";
import { useConfirm } from "@/Composables/useConfirm"
const confirm = useConfirm();
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
        const cleanData = data.data
        fillForm(cleanData);
        console.log(cleanData);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    } finally {
        isLoading.value = false
    }
};
const submit = async () => {

    const setConfirm = await confirm.ask({
        title: 'Pembatalan Transaksi',
        message: `Berhati-hati, kamu akan membatalkan transaksi ini. Apakah kamu yakin?`,
        confirmText: 'Ya, Batalkan',
        variant: 'danger'
    });

    if (setConfirm) {
        form.post(`/sales/cancel/${props.idSales}`, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onSuccess: () => {
                emit("saved");
                close();
            },
        });
    }
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

                        <div class="card border shadow-sm rounded-4 overflow-hidden mb-3">
                            <div
                                class="card-header bg-danger bg-opacity-10 border-bottom border-danger border-opacity-10 p-3">
                                <div
                                    class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">

                                    <div class="d-flex align-items-center gap-2 min-w-0">

                                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width: 48px; height: 48px;">
                                            <i class="bi bi-x-circle-fill fs-3"></i>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span
                                                    class="badge bg-white text-danger border border-danger-subtle fw-semibold p-2"
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
                                                        'Tidak ada email' }}</span>
                                            </div>
                                        </div>

                                    </div>


                                    <div class="align-self-start align-self-sm-center flex-shrink-0">
                                        <span class="badge bg-opacity-15 px-3 py-2 rounded-pill fw-medium" :class="{
                                            'bg-success': dataSales?.payment_status === 'LUNAS',
                                            'bg-warning': dataSales?.payment_status === 'DP',
                                        }" style="font-size: 12px;">
                                            <i class="bi me-1" :class="{
                                                ' bi-clock-history': dataSales?.payment_status === 'DP',
                                                ' bi-check-circle': dataSales?.payment_status === 'LUNAS',
                                            }"></i>
                                            {{ dataSales?.payment_status === 'LUNAS' ? 'Lunas' : 'Dana Awal (DP)' }}
                                        </span>
                                    </div>

                                </div>


                            </div>

                            <div class="card-body p-3 bg-white">

                                <div class="d-flex flex-column mb-3">
                                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center text-uppercase"
                                        style="font-size: 13px; letter-spacing: 0.5px;">
                                        <i class="bi bi-bag-check-fill text-success me-2 fs-5"></i> Daftar
                                        Produk Dipesan
                                    </h6>

                                    <div v-if="dataSales.products && dataSales.products.length > 0"
                                        class="d-flex align-items-center flex-wrap gap-2">
                                        <template v-for="(prod, index) in dataSales.products.slice(0, 4)"
                                            :key="prod.product?.id_product || index">
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 fw-medium py-1 px-3 rounded-pill d-flex align-items-center shadow-sm hover-shadow transition-all"
                                                style="font-size: 12.5px;">
                                                <i class="bi bi-box-seam me-2 opacity-75"></i>
                                                <span class="text-truncate" style="max-width: 180px;"
                                                    :title="prod.product?.name">
                                                    {{ prod.product?.name }}
                                                </span>
                                            </span>
                                        </template>

                                        <template v-if="dataSales.products.length > 4">
                                            <span
                                                class="badge bg-white text-dark border border-secondary border-opacity-25 fw-bold py-1 px-3 rounded-pill shadow-sm hover-shadow transition-all cursor-pointer"
                                                style="font-size: 12px;"
                                                :title="dataSales.products.slice(4).map(p => p.product?.name).join('\n')">
                                                +{{ dataSales.products.length - 4 }} Lainnya
                                            </span>
                                        </template>
                                    </div>

                                    <div v-else>
                                        <span
                                            class="badge bg-light text-muted border border-secondary border-opacity-10 fw-normal py-1 px-3 rounded-pill fst-italic"
                                            style="font-size: 12px;">
                                            <i class="bi bi-inbox me-1"></i> Belum ada produk
                                        </span>
                                    </div>
                                </div>

                                <hr class="border-secondary opacity-10 my-3">


                                <div class="d-flex flex-column flex-md-row gap-2">


                                    <div class="flex-fill p-2 bg-light rounded-3 border">
                                        <p class="mb-1 text-muted fw-semibold text-uppercase tracking-wide"
                                            style="font-size: 12px;">
                                            Total Deal
                                        </p>
                                        <div class="fw-bold text-dark" style="font-size: 16px;">
                                            {{ currencyFormat(dataSales?.deal_price) }}
                                        </div>
                                    </div>

                                    <div class="flex-fill p-2 bg-light rounded-3 border">
                                        <p class="mb-1 text-muted fw-semibold text-uppercase tracking-wide"
                                            style="font-size: 12px;">
                                            Telah Dibayar
                                        </p>
                                        <div class="fw-bold text-success" style="font-size: 16px;">
                                            {{ currencyFormat(dataSales?.paid_amount) }}
                                        </div>
                                    </div>

                                    <div class="flex-fill p-2 bg-light rounded-3 border">
                                        <p class="mb-1 text-muted fw-semibold text-uppercase tracking-wide"
                                            style="font-size: 12px;">
                                            Sisa Tagihan
                                        </p>
                                        <div class="fw-bold text-danger" style="font-size: 16px;">
                                            {{ currencyFormat(dataSales?.remaining_bill) }}
                                        </div>
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

                        <form-wrapper @submit.prevent="submit">
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
