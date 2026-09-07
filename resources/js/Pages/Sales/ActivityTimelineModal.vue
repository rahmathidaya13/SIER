<script setup>
import { ref, watch, computed } from "vue";
import { formatDate } from "@/Helpers/formatDate";
import { currencyFormat } from "@/Helpers/currencyFormat";
import axios from "axios";

const props = defineProps({
    show: Boolean,
    idSales: {
        type: String,
        default: null
    }
});

const emit = defineEmits(["update:show"]);

const isLoading = ref(false);
const activities = ref([]);
const customerName = ref("");
const phoneNumber = ref("");
const products = ref(null);
const paymentHistories = ref([]);
const sales = ref({});
const close = () => {
    emit("update:show", false);
    activities.value = [];
    customerName.value = "";
    phoneNumber.value = "";
    products.value = null;
    sales.value = {};
    paymentHistories.value = [];
};

const loadTimeline = async () => {
    if (!props.idSales) return;

    isLoading.value = true;
    try {
        // Kita manfaatkan endpoint show yang sudah membawa relasi lead.activity
        const { data } = await axios.get(`/sales/show/${props.idSales}`);
        customerName.value = data.lead?.name || 'Konsumen';
        phoneNumber.value = data.lead?.phone || 'Tidak Tersedia';
        activities.value = data.lead?.activity || [];
        products.value = data.sales_product || null;
        paymentHistories.value = data.payment_history || [];
        sales.value = data;
    } catch (error) {
        console.error("Gagal mengambil riwayat:", error);
    } finally {
        isLoading.value = false;
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            loadTimeline();
        }
    }
);

// Filter Cerdas: Menentukan Icon dan Warna berdasarkan isi teks
const getTimelineStyle = (description) => {
    if (description.includes('💰')) {
        return { icon: 'bi-cash-stack', color: 'success', bg: 'bg-success-subtle', text: 'text-success' };
    }
    if (description.includes('🎉') || description.includes('DEAL')) {
        return { icon: 'bi-bag-check-fill', color: 'primary', bg: 'bg-primary-subtle', text: 'text-primary' };
    }
    if (description.includes('❌') || description.includes('LOST')) {
        return { icon: 'bi-x-circle-fill', color: 'danger', bg: 'bg-danger-subtle', text: 'text-danger' };
    }
    // Default untuk Follow Up biasa
    return { icon: 'bi-chat-dots-fill', color: 'secondary', bg: 'bg-light', text: 'text-secondary' };
};

// Filter Cerdas untuk Metode Pembayaran (FinTech Style)
const getPaymentMethodStyle = (method) => {
    if (!method) return { icon: 'bi-cash-coin', bg: 'bg-secondary bg-opacity-10 text-secondary' };

    const m = method.toLowerCase();
    if (m.includes('transfer')) {
        return { icon: 'bi-bank', bg: 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' };
    }
    if (m.includes('qris')) {
        return { icon: 'bi-qr-code-scan', bg: 'bg-purple-subtle text-purple border border-purple border-opacity-25' };
    }
    if (m.includes('debit')) {
        return { icon: 'bi-credit-card-2-front', bg: 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' };
    }

    // Default: CASH / Tunai
    return { icon: 'bi-cash-stack', bg: 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' };
};
const getRoleName = (roleName) => {
    const roles = {
        'super_admin': {
            label: 'Head Office',
            color: 'bg-dark text-white'
        },
        'supervisor': {
            label: 'Supervisor',
            color: 'bg-primary text-white'
        },
        'admin': {
            label: 'Admin',
            color: 'bg-info text-dark'
        },
        'sales': {
            label: 'Sales',
            color: 'bg-success text-white'
        },
        'teknisi': {
            label: 'Teknisi',
            color: 'bg-secondary text-white'
        }
    };
    return roles[roleName] || 'bg-light text-dark border';
};
</script>

<template>
    <div v-if="props.show">
        <modal width="750px" size="modal-md" :icon="['text-primary', 'bi bi-clock-history']" :show="props.show"
            title="Riwayat Jejak Aktivitas" @closed="close" :mobileFullscreen="false">

            <template #body>
                <div class="bg-c-slate text-white p-3 rounded-3 mb-4 shadow-sm d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width: 45px; height: 45px;">
                        <i class="bi bi-person-lines-fill fs-4 text-white"></i>
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                        <p class="mb-0 text-white-50 small fw-medium text-uppercase tracking-wide"
                            style="font-size: 10.5px;">Timeline Konsumen</p>
                        <h5 class="mb-1 fw-bold">{{ customerName }}</h5>
                        <span class="small fw-medium">
                            <i class="bi bi-telephone-fill me-1"></i>
                            {{ phoneNumber }}
                        </span>
                        <div v-if="products && products.length > 0" class="d-flex flex-wrap gap-1 mt-1">

                            <template v-for="(prod, index) in products" :key="prod.id_product || index">
                                <span
                                    class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 fw-normal d-flex align-items-center py-1 px-2 rounded-pill shadow-sm"
                                    style="font-size: 11px;">
                                    <i class="bi bi-box-seam me-1 opacity-75"></i>
                                    <span class="text-truncate" style="max-width: 130px;" :title="prod.product.name">
                                        {{ prod.product.name }}
                                    </span>
                                </span>
                            </template>
                        </div>

                        <div v-else class="mt-1">
                            <span
                                class="badge bg-white bg-opacity-10 text-white-50 border border-white border-opacity-10 fw-normal py-1 px-2 rounded-pill fst-italic"
                                style="font-size: 10.5px;">
                                Belum ada produk
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="isLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3 small fw-medium">Menarik data dari arsip...</p>
                </div>

                <div v-else class="pb-3">

                    <div v-if="paymentHistories.length > 0" class="mb-4 border-bottom pb-4">

                        <div class="financial-receipt-wrapper bg-white border rounded-4 shadow-sm">

                            <div
                                class="bg-light px-4 py-3 border-bottom d-flex justify-content-between align-items-center rounded-top-4">
                                <span class="fw-bold text-dark" style="letter-spacing: 0.5px; font-size: 14px;">
                                    <i class="bi bi-receipt-cutoff text-success me-2 fs-5 align-middle"></i>
                                    BUKTI PEMBAYARAN MASUK
                                </span>
                                <span class="badge bg-success text-white rounded-pill px-3 py-2 shadow-sm">
                                    {{ paymentHistories.length }} Transaksi
                                </span>
                            </div>

                            <div class="p-0">
                                <div class="table-responsive">
                                    <table class="table table-borderless align-middle mb-0 custom-fin-table">
                                        <thead class="text-muted small text-uppercase" style="letter-spacing: 0.5px;">
                                            <tr class="align-middle">
                                                <th class="fw-bold" style="font-size: 11px;">Metode & Waktu</th>
                                                <th class="fw-bold text-center" style="font-size: 11px;">SALES</th>
                                                <th class="text-end fw-bold" style="font-size: 11px;">Nominal
                                                    Masuk</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="pay in paymentHistories" :key="pay.id_payment_history"
                                                class="border-bottom border-light row-hover-effect">
                                                <td class="px-4 py-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="icon-box rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                            :class="getPaymentMethodStyle(pay.payment_method).bg">
                                                            <i class="fs-5"
                                                                :class="getPaymentMethodStyle(pay.payment_method).icon"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bolder text-dark text-uppercase"
                                                                style="font-size: 13px; letter-spacing: 0.5px;">
                                                                {{ pay.payment_method }}
                                                            </div>
                                                            <div class="text-muted fw-medium" style="font-size: 11px;">
                                                                {{ formatDate(pay.created_at) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td class="text-center">
                                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                                        <span
                                                            class="badge badge-soft-success border border-success rounded-5 px-2 py-1 fw-medium text-capitalize"
                                                            style="font-size: 11px;">
                                                            <i class="bi bi-person-circle me-1"></i> {{ pay.user?.name
                                                                ||
                                                                'Sistem' }} {{
                                                                `(${getRoleName(pay.user.roles[0].name).label})` }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <td class="px-4 py-3 text-end">
                                                    <span class="fw-bold text-success font-monospace"
                                                        style="font-size: 16px;">
                                                        + {{ currencyFormat(pay.paid_amount) }}
                                                    </span>
                                                </td>

                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div
                                class="bg-light bg-gradient px-4 py-3 border-top d-flex justify-content-between align-items-center rounded-bottom-4">
                                <div class="d-flex flex-column">
                                    <span class="text-muted fw-bold text-uppercase mb-2"
                                        style="font-size: 12px; letter-spacing: 0.5px;">Harga Deal</span>
                                    <span class="text-muted fw-bold text-uppercase"
                                        style="font-size: 12px; letter-spacing: 0.5px;">Total Telah Dibayar</span>

                                </div>

                                <div class="d-flex flex-column">
                                    <span class="fw-bolder text-muted fs-5 font-monospace">
                                        {{ currencyFormat(sales.deal_price) }}
                                    </span>

                                    <span class="fw-bolder text-dark fs-5 font-monospace">
                                        {{currencyFormat(paymentHistories.reduce((sum, item) => sum +
                                            Number(item.paid_amount), 0))}}
                                    </span>

                                </div>
                            </div>

                        </div>
                    </div>

                    <div v-if="activities.length > 0" class="timeline-container pb-3">

                        <h5 class="fw-bold mb-3">Riwayat Aktivitas</h5>
                        <div v-for="(act, index) in activities" :key="act.id_lead_activity"
                            class="timeline-item position-relative mb-4">

                            <div v-if="index !== activities.length" class="timeline-line"></div>

                            <div class="d-flex gap-3">
                                <div class="timeline-icon flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center shadow-sm z-2"
                                    :class="getTimelineStyle(act.description).bg"
                                    style="width: 40px; height: 40px; border: 3px solid #fff;">
                                    <i class="fs-5"
                                        :class="[getTimelineStyle(act.description).icon, getTimelineStyle(act.description).text]"></i>
                                </div>

                                <div class="flex-grow-1 bg-white border rounded-3 p-3 shadow-sm">
                                    <div
                                        class="d-xl-flex d-block justify-content-between align-items-start mb-2 border-bottom pb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="badge bg-light text-dark border small">
                                                <i class="bi bi-person-fill me-1"></i>
                                                <span class="text-capitalize">
                                                    {{ act.user?.name || 'Sistem' }} - {{
                                                        getRoleName(act.user?.roles[0]?.name).label }}
                                                </span>
                                            </div>
                                        </div>
                                        <span class="text-muted small fw-medium ms-1" style="font-size: 11px;">
                                            <i class="bi bi-calendar2-event me-1"></i> {{ formatDate(act.created_at) }}
                                        </span>
                                    </div>

                                    <p class="mb-0 text-dark"
                                        style="font-size: 14px; white-space: pre-line; line-height: 1.5;">
                                        {{ act.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!activities.length && !paymentHistories.length" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted opacity-25 d-block mb-3"></i>
                        <h6 class="text-muted fw-bold">Belum Ada Riwayat</h6>
                        <p class="text-muted small">Konsumen ini belum memiliki jejak rekam atau aktivitas pembayaran.
                        </p>
                    </div>

                </div>

            </template>

            <template #footer>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary fw-bold px-4 shadow-sm" @click="close">
                        <i class="bi bi-x-circle me-1"></i> TUTUP
                    </button>
                </div>
            </template>

        </modal>
    </div>
</template>

<style scoped>
.timeline-container {
    position: relative;
    max-height: 500px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 10px;
}

/* Kustomisasi Scrollbar agar elegan */
.timeline-container::-webkit-scrollbar {
    width: 6px;
}

.timeline-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.timeline-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.timeline-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Garis Vertikal Timeline */
.timeline-line {
    position: absolute;
    left: 19px;
    top: 40px;
    bottom: -20px;
    width: 2px;
    background-color: #e2e8f0;
    z-index: 1;
}

.tracking-wide {
    letter-spacing: 1px;
}


/* Styling Khusus FinTech Table */
.financial-receipt-wrapper {
    transition: all 0.3s ease;
}

.icon-box {
    width: 42px;
    height: 42px;
}

.row-hover-effect {
    transition: background-color 0.2s ease-in-out;
}

.row-hover-effect:hover {
    background-color: #f8fafc;
    /* Warna Slate-50 yang sangat lembut */
}

.font-monospace {
    font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace !important;
}

/* Kustomisasi warna ungu untuk QRIS jika bootstrap bawaan tidak punya */
.bg-purple-subtle {
    background-color: #f3e8ff !important;
}

.text-purple {
    color: #7e22ce !important;
}

.border-purple {
    border-color: #d8b4fe !important;
}
</style>
