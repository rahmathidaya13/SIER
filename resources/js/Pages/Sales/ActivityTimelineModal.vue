<script setup>
import { ref, watch, computed } from "vue";
import { formatDate } from "@/Helpers/formatDate";
import { currencyFormat } from "@/Helpers/currencyFormat";
import axios from "axios";

const props = defineProps({
    show: Boolean,
    idSales: {
        type: String,
        default: null,
    },
});
const emit = defineEmits(["update:show"]);

const isLoading = ref(false);
const activities = ref([]);
const customerName = ref("");
const phoneNumber = ref("");
const products = ref(null);
const paymentHistories = ref([]);
const sales = ref({});

// nav tabs
const activeTab = ref("transactions");
const cancellation = ref(null);
const close = () => {
    emit("update:show", false);
    activities.value = [];
    customerName.value = "";
    phoneNumber.value = "";
    products.value = null;
    sales.value = {};
    paymentHistories.value = [];

    activeTab.value = "transactions";
    cancellation.value = null;
};

const loadTimeline = async () => {
    if (!props.idSales) return;

    isLoading.value = true;
    try {
        // Kita manfaatkan endpoint show yang sudah membawa relasi lead.activity
        const { data } = await axios.get(`/sales/show/${props.idSales}`);
        customerName.value = data.lead?.name || "Konsumen";
        phoneNumber.value = data.lead?.phone || "Tidak Tersedia";
        activities.value = data.lead?.activity || [];
        products.value = data.sales_product || null;
        paymentHistories.value = data.payment_history || [];
        sales.value = data;

        cancellation.value = data.cancelled_at
            ? {
                cancelled_at: data.cancelled_at,
                cancelled_by: data.cancelled_by,
                cancelled_reason: data.cancelled_reason,
                cancelled_notes: data.cancelled_notes,
                user_name: data.user?.name,
                role_name: data.user?.roles[0].name,
            }
            : null;
        console.log(data);
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
            activeTab.value = "transactions";
            loadTimeline();
        }
    }
);

// Filter Cerdas: Menentukan Icon dan Warna berdasarkan isi teks
const getTimelineStyle = (description) => {
    if (description.includes("💰")) {
        return {
            icon: "bi-cash-stack",
            color: "success",
            bg: "bg-success-subtle",
            text: "text-success",
        };
    }
    if (description.includes("🎉") || description.includes("DEAL")) {
        return {
            icon: "bi-bag-check-fill",
            color: "primary",
            bg: "bg-primary-subtle",
            text: "text-primary",
        };
    }
    if (description.includes("❌") || description.includes("LOST")) {
        return {
            icon: "bi-x-circle-fill",
            color: "danger",
            bg: "bg-danger-subtle",
            text: "text-danger",
        };
    }
    // Default untuk Follow Up biasa
    return {
        icon: "bi-chat-dots-fill",
        color: "secondary",
        bg: "bg-light",
        text: "text-secondary",
    };
};

// Filter Cerdas untuk Metode Pembayaran (FinTech Style)
const getPaymentMethodStyle = (method) => {
    if (!method)
        return { icon: "bi-cash-coin", bg: "bg-secondary bg-opacity-10 text-secondary" };

    const m = method.toLowerCase();
    if (m.includes("transfer")) {
        return {
            icon: "bi-bank",
            bg: "bg-info bg-opacity-10 text-info border border-info border-opacity-25",
        };
    }
    if (m.includes("qris")) {
        return {
            icon: "bi-qr-code-scan",
            bg: "bg-purple-subtle text-purple border border-purple border-opacity-25",
        };
    }
    if (m.includes("debit")) {
        return {
            icon: "bi-credit-card-2-front",
            bg: "bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25",
        };
    }

    // Default: CASH / Tunai
    return {
        icon: "bi-cash-stack",
        bg: "bg-success bg-opacity-10 text-success border border-success border-opacity-25",
    };
};
const getRoleName = (roleName) => {
    const roles = {
        super_admin: {
            label: "Head Office",
            color: "bg-dark text-white",
        },
        supervisor: {
            label: "Supervisor",
            color: "bg-primary text-white",
        },
        admin: {
            label: "Admin",
            color: "bg-info text-dark",
        },
        sales: {
            label: "Sales",
            color: "bg-success text-white",
        },
        teknisi: {
            label: "Teknisi",
            color: "bg-secondary text-white",
        },
    };
    return roles[roleName] || {
        label: 'User',
        color: 'bg-light text-dark border',
    };
};

const getCancellationReasonLabel = (reason) => {
    const reasons = {
        CUSTOMER_CANCELLED: 'Pelanggan membatalkan pesanan',
        PAYMENT_FAILED: 'Pembayaran tidak diselesaikan',
        ORDER_UNFULFILLABLE: 'Pesanan tidak dapat dipenuhi',
        DATA_ERROR: 'Kesalahan input transaksi',
        DUPLICATE: 'Duplikasi transaksi',
        OTHER: 'Lainnya',
    };

    return reasons[reason] || reason;
};
</script>

<template>
    <div v-if="props.show">
        <modal width="750px" size="modal-md" :icon="['text-primary', 'bi bi-clock-history']" :show="props.show"
            title="Riwayat Transaksi & Aktivitas" @closed="close" :mobileFullscreen="false">
            <template #body>



                <div v-if="isLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3 small fw-medium">Menarik data dari arsip...</p>
                </div>

                <div v-else class="pb-3">

                    <div class="card mb-3 overflow-hidden shadow-sm border bg-c-slate border-secondary">
                        <div class="row g-0">
                            <div class="col-md-2 d-flex align-items-center justify-content-center">
                                <span style="font-size: 5rem;"><i
                                        class="bi bi-person-bounding-box text-muted"></i></span>
                            </div>
                            <div class="col-md-10">
                                <div class="card-body">
                                    <h6 class="card-title text-muted small text-uppercase">
                                        Timeline Konsumen
                                    </h6>
                                    <h5 class="mb-2 fw-bold text-truncate" style="max-width: 100%;">
                                        {{ customerName }}
                                    </h5>
                                    <div class="small fw-medium text-white-75 mb-3">
                                        <i class="bi bi-telephone-fill me-1"></i> {{ phoneNumber }}
                                    </div>

                                    <div v-if="products && products.length > 0" class="d-flex flex-wrap gap-2">
                                        <template v-for="(prod, index) in products.slice(0, 5)"
                                            :key="prod.id_product || index">
                                            <span
                                                class="badge bg-white text-dark fw-normal d-flex align-items-center py-2 px-2 rounded-pill"
                                                style="font-size: 12px;">
                                                <i class="bi bi-box-seam me-2"></i>
                                                <span class="text-truncate" style="max-width: 200px;"
                                                    :title="prod.product.name">
                                                    {{ prod.product.name }}
                                                </span>
                                            </span>
                                        </template>

                                        <!-- Badge sisa produk (Muncul jika lebih dari 3) -->
                                        <span v-if="products.length > 5"
                                            class="badge bg-white text-dark fw-bold d-flex align-items-center py-1 px-3 rounded-pill shadow-sm transition-all"
                                            style="font-size: 12px;"
                                            :title="products.slice(5).map(p => p.product.name).join('\n')">
                                            +{{ products.length - 5 }} Lainnya
                                        </span>
                                    </div>

                                    <div v-else>
                                        <span
                                            class="badge bg-white bg-opacity-10 text-white-50 border border-white border-opacity-10 fw-normal py-1 px-3 rounded-pill fst-italic"
                                            style="font-size: 11px;">
                                            Belum ada produk
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>




                    <div class="history-tabs mb-4">
                        <div class="nav nav-pills nav-fill gap-2">
                            <button type="button" class="nav-link" :class="{
                                active: activeTab === 'transactions',
                            }" @click="activeTab = 'transactions'">
                                <i class="bi bi-receipt-cutoff me-1"></i>
                                Riwayat Transaksi
                            </button>

                            <button type="button" class="nav-link" :class="{
                                active: activeTab === 'activities',
                            }" @click="activeTab = 'activities'">
                                <i class="bi bi-activity me-1"></i>
                                Aktivitas
                            </button>

                            <button type="button" class="nav-link" :class="{
                                active: activeTab === 'cancellation',
                            }" @click="activeTab = 'cancellation'">
                                <i class="bi bi-x-octagon me-1"></i>
                                Pembatalan
                            </button>
                        </div>
                    </div>
                    <div class="tab-content">
                        <div v-if="activeTab === 'transactions'" class="animation-fade-in">
                            <div v-if="paymentHistories.length > 0"
                                class="financial-receipt-wrapper bg-white border rounded-4 shadow-sm">
                                <div
                                    class="bg-light px-4 py-3 border-bottom d-flex justify-content-between align-items-center rounded-top-4">
                                    <span class="fw-bold text-dark" style="letter-spacing: 0.5px; font-size: 14px">
                                        <i class="bi bi-receipt-cutoff text-success me-2 fs-5 align-middle"></i>
                                        BUKTI PEMBAYARAN
                                    </span>
                                    <span class="badge bg-success text-white rounded-pill px-3 py-2 shadow-sm">
                                        {{ paymentHistories.length }} Transaksi
                                    </span>
                                </div>

                                <div class="p-0">
                                    <div class="table-responsive">
                                        <table class="table table-borderless align-middle mb-0 custom-fin-table">
                                            <thead class="text-muted small text-uppercase"
                                                style="letter-spacing: 0.5px">
                                                <tr class="align-middle">
                                                    <th class="fw-bold" style="font-size: 11px">Metode & Waktu</th>
                                                    <th class="fw-bold text-center" style="font-size: 11px">SALES</th>
                                                    <th class="text-end fw-bold" style="font-size: 11px">
                                                        Nominal
                                                    </th>
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
                                                                    style="font-size: 13px; letter-spacing: 0.5px">
                                                                    {{ pay.payment_method }} {{ pay.payment_method ===
                                                                        'transfer' || pay.payment_method === 'debit' ?
                                                                        '- ' + pay.bank_name : '' }}
                                                                </div>
                                                                <div class="text-muted fw-medium"
                                                                    style="font-size: 11px">
                                                                    {{ formatDate(pay.created_at) }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <div
                                                            class="d-flex align-items-center gap-2 justify-content-center">
                                                            <span
                                                                class="badge badge-soft-success border border-success rounded-5 px-2 py-1 fw-medium text-capitalize"
                                                                style="font-size: 11px">
                                                                <i class="bi bi-person-circle me-1"></i>
                                                                {{ pay.user?.name || "Sistem" }}
                                                                {{ `(${getRoleName(pay.user.roles[0].name).label})` }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3 text-end">
                                                        <span class="fw-bold text-success font-monospace"
                                                            style="font-size: 16px">
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
                                            style="font-size: 12px; letter-spacing: 0.5px">Harga Deal</span>
                                        <span class="text-muted fw-bold text-uppercase"
                                            style="font-size: 12px; letter-spacing: 0.5px">Total
                                            Telah Dibayar</span>
                                    </div>
                                    <div class="d-flex flex-column text-end">
                                        <span class="fw-bolder text-muted fs-5 font-monospace">{{
                                            currencyFormat(sales.deal_price)
                                        }}</span>
                                        <span class="fw-bolder text-dark fs-5 font-monospace">{{
                                            currencyFormat(
                                                paymentHistories.reduce(
                                                    (sum, item) => sum + Number(item.paid_amount),
                                                    0
                                                )
                                            )
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="empty-state">
                                <div class="empty-icon bg-light text-muted mb-3">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <h6 class="text-muted fw-bold">Belum Ada Transaksi</h6>
                                <p class="text-muted small">
                                    Konsumen ini belum memiliki riwayat pembayaran.
                                </p>
                            </div>
                        </div>


                        <div v-if="activeTab === 'activities'" class="animation-fade-in">
                            <div v-if="activities.length > 0" class="timeline-container pb-3">
                                <div v-for="(act, index) in activities" :key="act.id_lead_activity"
                                    class="timeline-item position-relative mb-4">
                                    <div v-if="index !== activities.length" class="timeline-line"></div>
                                    <div class="d-flex gap-3">
                                        <div class="timeline-icon flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center shadow-sm z-2"
                                            :class="getTimelineStyle(act.description).bg"
                                            style="width: 40px; height: 40px; border: 3px solid #fff">
                                            <i class="fs-5" :class="[
                                                getTimelineStyle(act.description).icon,
                                                getTimelineStyle(act.description).text,
                                            ]"></i>
                                        </div>
                                        <div class="flex-grow-1 bg-white border rounded-3 p-3 shadow-sm">
                                            <div
                                                class="d-xl-flex d-block justify-content-between align-items-start mb-2 border-bottom pb-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="badge bg-light text-dark border small">
                                                        <i class="bi bi-person-fill me-1"></i>
                                                        <span class="text-capitalize">{{ act.user?.name || "Sistem" }} -
                                                            {{ getRoleName(act.user?.roles[0]?.name).label }}</span>
                                                    </div>
                                                </div>
                                                <span class="text-muted small fw-medium ms-1" style="font-size: 11px">
                                                    <i class="bi bi-calendar2-event me-1"></i>
                                                    {{ formatDate(act.created_at) }}
                                                </span>
                                            </div>
                                            <p class="mb-0 text-dark"
                                                style="font-size: 14px; white-space: pre-line; line-height: 1.5">
                                                {{ act.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="empty-state">
                                <div class="empty-icon bg-light text-muted mb-3">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <h6 class="text-muted fw-bold">Belum Ada Riwayat</h6>
                                <p class="text-muted small">
                                    Belum ada jejak rekam atau aktivitas untuk konsumen ini.
                                </p>
                            </div>
                        </div>

                        <div v-if="activeTab === 'cancellation'" class="animation-fade-in">
                            <div v-if="cancellation" class="card border shadow-sm overflow-hidden">
                                <div
                                    class="card-header bg-danger-subtle border-bottom border-danger border-opacity-25 px-4 py-3 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div
                                            class="cancellation-icon rounded-circle bg-danger text-white d-flex align-items-center justify-content-center">
                                            <i class="bi bi-x-lg"></i>
                                        </div>

                                        <div>
                                            <div class="fw-bold text-danger" style="font-size: 14px">
                                                TRANSAKSI DIBATALKAN
                                            </div>

                                            <div class="text-muted small">
                                                {{ sales.invoice }}
                                            </div>
                                        </div>
                                    </div>

                                    <span class="badge bg-danger rounded-pill px-3 py-2"> CANCELLED </span>
                                </div>

                                <div class="card-body p-4">
                                    <div class="row mb-4">
                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <div class="text-muted text-uppercase fw-bold mb-1"
                                                style="font-size: 10px; letter-spacing: 0.6px">
                                                Alasan Pembatalan
                                            </div>

                                            <div class="fw-semibold text-dark text-capitalize" style="font-size: 14px;">
                                                {{ getCancellationReasonLabel(cancellation.cancelled_reason) }}
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3 mb-md-0">
                                            <div class="text-muted text-uppercase fw-bold mb-1"
                                                style="font-size: 10px; letter-spacing: 0.6px">
                                                Dibatalkaan Oleh
                                            </div>

                                            <div class="fw-semibold text-dark text-capitalize" style="font-size: 14px;">
                                                <i class="bi bi-person-circle text-danger me-1"></i>
                                                {{ cancellation.user_name || "Sistem" }} - {{ cancellation.role_name }}
                                            </div>
                                        </div>


                                        <div class="col-md-4">
                                            <div class="text-muted text-uppercase fw-bold mb-1"
                                                style="font-size: 10px; letter-spacing: 0.6px">
                                                Waktu Pembatalan
                                            </div>

                                            <div class="fw-semibold text-dark" style="font-size: 14px;">
                                                <i class="bi bi-calendar2-event text-danger me-1"></i>
                                                {{ formatDate(cancellation.cancelled_at) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="cancellation.cancelled_notes" class="bg-light border rounded-3 p-3">
                                        <div class="text-muted text-uppercase fw-bold mb-2"
                                            style="font-size: 10px; letter-spacing: 0.6px">
                                            Catatan Pembatalan
                                        </div>

                                        <div class="text-dark small" style="white-space: pre-line; line-height: 1.6">
                                            {{ cancellation.cancelled_notes }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state">
                                <div class="empty-icon bg-success-subtle text-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>

                                <h6 class="fw-bold mt-3">Transaksi Belum Dibatalkan</h6>

                                <p class="text-muted small mb-0">
                                    Tidak terdapat riwayat pembatalan untuk transaksi ini.
                                </p>
                            </div>

                        </div>
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
.animation-fade-in {
    animation: fadeInSlideUp 0.35s ease-out forwards;
}

@keyframes fadeInSlideUp {
    from {
        opacity: 0;
        transform: translateY(-15px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

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
    font-family: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New",
        monospace !important;
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

/* Kustomisasi Tab History */
.history-tabs {
    position: relative;
}

.history-tabs .nav {
    background: #f5f5f5;
    padding: 5px;
    border: 1px solid #e9ecef;
    border-radius: 12px;
}

.history-tabs .nav-link {
    border: 0;
    border-radius: 9px;
    color: #6c757d;
    font-size: 12px;
    font-weight: 600;
    padding: 10px 12px;
    transition: color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
}

.history-tabs .nav-link:hover {
    color: #212529;
    background: #ffffff;
}

.history-tabs .nav-link.active {
    color: #f5f2f2;
    background: #81afe4;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.cancellation-icon {
    width: 36px;
    height: 36px;
}

.empty-state {
    min-height: 260px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px 20px;
}

.empty-state>i {
    font-size: 48px;
    color: #cbd5e1;
    opacity: 0.7;
}

.empty-icon {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}
</style>
