<script setup>
import { ref, watch, computed } from "vue";
import { formatDate } from "@/Helpers/formatDate";
import { currencyFormat } from "@/Helpers/currencyFormat";
import axios from "axios";
import { useRequest } from "@/Composables/useRequest";
const { isLoading, requests } = useRequest();
const props = defineProps({
    show: Boolean,
    idSales: {
        type: String,
        default: null,
    },
});
const emit = defineEmits(["update:show"]);

const activities = ref([]);
const products = ref(null);
const paymentHistories = ref([]);
const sales = ref({});

// nav tabs
const activeTab = ref("transactions");
const cancellation = ref(null);
const close = () => {
    emit("update:show", false);
    activities.value = [];
    products.value = null;
    sales.value = {};
    paymentHistories.value = [];

    activeTab.value = "transactions";
    cancellation.value = null;
};


const loadSales = async (showLoading = true) => {
    return requests(async () => {
        const { data } = await axios.get(
            route('sales.show', props.idSales)
        );
        const salesData = data.data;
        sales.value = salesData || {};
        products.value = salesData?.products || null;
        cancellation.value = salesData?.cancelled_at
            ? {
                cancelled_at: salesData.cancelled_at,
                cancelled_by: salesData.cancelled_by,
                cancelled_reason: salesData.cancelled_reason,
                cancelled_notes: salesData.cancelled_notes,
                user_name: salesData.cancelledBy?.name,
                role_name: salesData.cancelledBy?.role_name
            }
            : null;
        console.log(data.data);
    },
        "Gagal mengambil data transaksi",
        showLoading
    );
};

const loadActivity = async (page = 1, showLoading = true) => {
    if (!props.idSales) return;
    return requests(
        async () => {
            const { data } = await axios.get(
                route('sales.activities', props.idSales),
                {
                    params: { page },
                }
            );
            activities.value = data.data || [];

        },
        'Gagal mengambil riwayat aktivitas:',
        showLoading
    );
}

const loadPaymentHistory = async (page = 1, showLoading = true) => {
    if (!props.idSales) return;
    return requests(
        async () => {
            const { data } = await axios.get(
                route('sales.payment.history', props.idSales),
                {
                    params: { page },
                }
            );
            paymentHistories.value = data.data || [];
            console.log(data.data);
        },
        'Gagal mengambil riwayat pembayaran:',
        showLoading
    );
}
const loadInitialData = async () => {
    if (!props.idSales) return;
    isLoading.value = true;
    try {
        await Promise.all([
            loadActivity(1, false),
            loadPaymentHistory(1, false),
            loadSales(false)
        ])
    } catch (error) {
        console.error('Gagal memuat detail penjualan:', error);
    } finally {
        isLoading.value = false
    }
}
watch(
    () => props.show,
    async (isOpen) => {
        if (isOpen) {
            activeTab.value = "transactions";
            await loadInitialData();
        }
    },
    { immediate: true }
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
const getShortDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}
const getTimeOnly = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div v-if="props.show">
        <modal width="750px" size="modal-md" :icon="['text-primary', 'bi bi-clock-history']" :show="props.show"
            title="Riwayat Transaksi & Aktivitas" @closed="close" :mobileFullscreen="false" :footer="false">
            <template #body>

                <div v-if="isLoading" class="d-flex flex-column align-items-center justify-content-center p-5">
                    <div class="spinner-border text-primary mb-3"></div>

                    <div class="fw-semibold">
                        Sedang mengambil data...
                    </div>

                    <small class="text-muted">
                        Mohon tunggu sebentar
                    </small>
                </div>

                <div v-else class="pb-3">

                    <div class="card bg-info bg-opacity-10 border rounded-4 mb-3 overflow-auto">
                        <div class="card-body">

                            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                <div class="flex-shrink-0 bg-primary-subtle text-primary rounded-circle d-flex justify-content-center align-items-center shadow-sm"
                                    style="width: 58px; height: 58px;">
                                    <i class="bi bi-person-fill fs-3"></i>
                                </div>

                                <div class="flex-grow-1">
                                    <h5 class="fw-bold text-dark mb-1 text-capitalize">{{ sales.lead?.name }}</h5>
                                    <h6 class="fw-semibold text-muted mb-1 text-capitalize">{{ sales.invoice }}</h6>
                                    <div class="d-flex flex-wrap gap-3 text-muted" style="font-size: 13px;">
                                        <span class="d-flex align-items-center">
                                            <i class="bi bi-telephone-fill me-1 text-secondary opacity-75"></i> {{
                                                sales.lead?.phone }}
                                        </span>
                                        <span class="d-flex align-items-center">
                                            <i class="bi bi-envelope-fill me-1 text-secondary opacity-75"></i> {{
                                                sales.lead?.email || 'Tidak ada email' }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="text-sm-end mt-2 mt-sm-0 border-sm-start border-secondary border-opacity-25 ps-sm-4">
                                    <div class="text-muted small fw-semibold text-uppercase tracking-wide mb-1"
                                        style="font-size: 11px;">
                                        Total Produk
                                    </div>
                                    <span class="badge bg-primary bg-gradient fs-5 px-4 py-2 rounded-pill shadow-sm">
                                        {{ sales.product_count || 0 }}
                                    </span>
                                </div>

                            </div>

                            <div v-if="sales.notes"
                                class="mt-4 p-3 bg-light border-start border-4 border-warning rounded-end-3 overflow-hidden">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-journal-text text-warning mt-1 fs-5 flex-shrink-0"></i>
                                    <div class="flex-grow-1" style="min-width: 0;">
                                        <div class="text-muted fw-bold text-uppercase tracking-wide mb-1"
                                            style="font-size: 10.5px;">
                                            Catatan
                                        </div>
                                        <p class="mb-0 text-dark"
                                            style="font-size: 13.5px; white-space: pre-line; line-height: 1.5; word-break: break-word; overflow-wrap: break-word;">
                                            {{ sales.notes }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <hr class="border-secondary opacity-10 my-3">

                            <div>
                                <h6 class="fw-bold text-dark mb-3 d-flex align-items-center text-uppercase"
                                    style="font-size: 13px; letter-spacing: 0.5px;">
                                    <i class="bi bi-bag-check-fill text-success me-2 fs-5"></i> Daftar Produk Dipesan
                                </h6>

                                <div v-if="sales.products && sales.products.length > 0"
                                    class="d-flex align-items-center flex-wrap gap-2">
                                    <template v-for="(prod, index) in sales.products.slice(0, 4)"
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

                                    <template v-if="sales.products.length > 4">
                                        <span
                                            class="badge bg-white text-dark border border-secondary border-opacity-25 fw-bold py-1 px-3 rounded-pill shadow-sm hover-shadow transition-all cursor-pointer"
                                            style="font-size: 12px;"
                                            :title="sales.products.slice(4).map(p => p.product?.name).join('\n')">
                                            +{{ sales.products.length - 4 }} Lainnya
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

                        <!-- Transactions Tabs -->
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
                                                                {{ `(${getRoleName(pay.user.role_name).label})` }}
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
                                        <span class="text-muted fw-bold text-uppercase mt-4"
                                            style="font-size: 12px; letter-spacing: 0.5px">Status Pembayaran</span>
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
                                        <div style="font-size: 14px; letter-spacing: 0.5px"
                                            class="badge rounded-2 px-3 py-2 mt-2 text-uppercase font-monospace" :class="{
                                                'badge-soft-warning': sales.payment_status === 'DP',
                                                'badge-soft-success': sales.payment_status === 'LUNAS'
                                            }">
                                            {{ sales.payment_status === 'DP' ? 'Dana Awal (DP)' :
                                                'Lunas' }}
                                        </div>
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

                            <div v-if="sales.notes !== null" class="bg-light border rounded-3 p-3 mt-3">
                                <div class="text-muted text-uppercase fw-bold mb-2"
                                    style="font-size: 10px; letter-spacing: 0.6px">
                                    Catatan Transaksi
                                </div>

                                <div class="text-dark small" style="white-space: pre-line; line-height: 1.6">
                                    {{ sales.notes }}
                                </div>
                            </div>
                        </div>

                        <!-- Aktivity Tabs -->
                        <div v-if="activeTab === 'activities'" class="animation-fade-in">
                            <div v-if="activities.length > 0" class="tracking-container pb-3">

                                <div v-for="(act, index) in activities" :key="act.id_lead_activity"
                                    class="d-flex align-items-stretch gap-3">

                                    <div class="flex-shrink-0 text-end pe-3" style="width: 70px; padding-top: 2px;">
                                        <div class="fw-medium tracking-date text-muted">
                                            {{ getShortDate(act.created_at) }}
                                        </div>
                                        <div class="tracking-time text-muted opacity-75">
                                            {{ getTimeOnly(act.created_at) }}
                                        </div>
                                    </div>

                                    <div class="position-relative d-flex flex-column align-items-center"
                                        style="width: 5px;">

                                        <div class="rounded-circle z-2"
                                            :class="index === 0 ? 'bg-success' : 'bg-secondary opacity-50'"
                                            :style="index === 0 ? 'width: 10px; height: 10px; margin-top: 4px; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.2);' : 'width: 8px; height: 8px; margin-top: 5px;'">
                                        </div>

                                        <div v-if="index !== activities.length - 1"
                                            class="bg-secondary opacity-25 position-absolute"
                                            style="width: 2px; top: 16px; bottom: -6px; left: 50%; transform: translateX(-50%); z-index: 1;">
                                        </div>
                                    </div>

                                    <div class="flex-grow-1 ps-3 pb-3">

                                        <div class="tracking-desc mb-1"
                                            :class="index === 0 ? 'text-success fw-medium' : 'text-muted'"
                                            style="white-space: pre-line;">
                                            {{ act.description }}
                                        </div>

                                        <div
                                            class="tracking-user text-muted opacity-75 d-flex align-items-center gap-1 mt-1">
                                            <i class="bi bi-person"></i>
                                            <span class="text-capitalize">{{ act.user?.name || 'Sistem' }}</span>
                                            <span v-if="act.created_at !== act.updated_at" class="fst-italic ms-1">
                                                (Diubah: {{ act.user?.name }})
                                            </span>
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


/* Tracking Activity UI Styles */
.tracking-container {
    max-height: 520px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 8px;
}

.tracking-container::-webkit-scrollbar {
    width: 5px;
}

.tracking-container::-webkit-scrollbar-track {
    background: transparent;
}

.tracking-container::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.tracking-container::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

/* Tipografi Presisi */
.tracking-date {
    font-size: 12px;
    line-height: 1.2;
    letter-spacing: 0.2px;
}

.tracking-time {
    font-size: 11px;
    margin-top: 2px;
}

.tracking-desc {
    font-size: 14px;
    line-height: 1.55;
    letter-spacing: 0.1px;
    word-break: break-word;
    white-space: pre-wrap;
    word-wrap: break-word;
    hyphens: auto;
}

.tracking-user {
    font-size: 12px;
}
</style>
