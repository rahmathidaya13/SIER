<script setup>
import { Form, Head, usePage, router, Link } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import { useFilter } from '@/Composables/useFilter';
import { useConfirm } from "@/Composables/useConfirm"
import { highlight } from "@/Helpers/Highlight";
import axios from 'axios';
import { formatDate } from '@/Helpers/formatDate';
import { currencyFormat } from '@/Helpers/currencyFormat';
import PaymentModal from './PaymentModal.vue';
import ActivityTimelineModal from './ActivityTimelineModal.vue';
import { hasRole, hasPermission } from '@/Composables/useAuth';

const props = defineProps({
    sales: Object,
    filters: Object,
    stats: Object,
    branch: Array
});
const {
    filters,
    isLoading,
} = useFilter({
    routeName: "sales.index",
    initialFilters: {
        keyword: props.filters.keyword ?? '',
        limit: props.filters.limit ?? 10,
        order_by: props.filters.order_by ?? 'latest',
        branch: props.filters.branch ?? 'all',
        page: props.filters.page ?? 1,
    },
    watchFields: [
        'keyword',
        'limit',
        'order_by',
        'branch'
    ],
    only: ['sales', 'filters', 'stats', 'branch'],
    debounceTime: 500,
});

const showPaymentModal = ref(false);
const showActivityModal = ref(false);
const selectedIdSale = ref(null);
const openPaymentModal = (data) => {
    selectedIdSale.value = data.id_sales;
    showPaymentModal.value = true;
}
const openActivityModal = (data) => {
    selectedIdSale.value = data.id_sales;
    showActivityModal.value = true;
}
const cancelClosing = (data) => {
    selectedIdSale.value = data.id_sales;
    showPaymentModal.value = true;

}
const header = [
    {
        label: "No",
        key: "__index",
        attrs: {
            class: 'text-center td-number',
        },
    },
    {
        label: "INVOICE & TANGGAL",
        key: "invoice_date",
        attrs: {
            style: {
                width: '15%'
            },
            class: 'text-center',
        },
    },
    {
        label: "PELANGGAN & PRODUK",
        key: "customer_product",
        attrs: {
            style: {
                width: '25%'
            },
            class: 'text-start',
        },
    },
    {
        label: "HARGA DEAL",
        key: "deal_prices",
        attrs: {
            style: {
                width: '15%'
            },
            class: 'text-end',
        },
    },
    {
        label: "STATUS BAYAR",
        key: "payment_status",
        attrs: {
            style: {
                width: '25%'
            },
            class: 'text-center',
        },
    },
    {
        label: "Dibuat Oleh",
        key: "sales_name",
        attrs: {
            class: 'text-center',
        },
    },

    {
        label: "Aksi",
        key: "actions",
        attrs: {
            class: 'text-center'
        },
    },
];

const payActions = [
    {
        label: 'Lihat history aktivitas',
        action: 'history',
        protectedSelf: true,
        icon: 'bi bi-clock-history',
        title: 'Lihat history aktivitas',
        class: 'btn btn-c-slate',
    },
    {
        label: 'Batal pembayaran',
        action: 'cancelClosing',
        protectedSelf: true,
        icon: 'bi bi-x-circle-fill',
        title: 'Batal pembayaran',
        class: 'btn btn-c-danger',
    },

    {
        label: 'Lakukan pembayaran',
        action: 'pay',
        protectedSelf: true,
        icon: 'bi bi-wallet2',
        title: 'Lakukan pembayaran',
        class: 'btn btn-c-success',
        show: (item) => item.payment_status !== 'LUNAS',
    },
];
const branchOptions = computed(() => [
    {
        label: 'Semua Cabang',
        value: 'all',
    },
    ...props.branch.map(data => ({
        label: data.name,
        value: data.name,
    }))
]);
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
const keywordRef = ref(null);
onMounted(() => {
    keywordRef.value.focus();
})
</script>
<template>

    <Head title="Rekap Closing" />
    <app-layout>
        <template #content>
            <div class="row g-3 view-section">
                <div class="col-12">
                    <callout />

                    <div class="row g-3 mb-4">
                        <div class="col-md-12 col-12 col-xl-4">
                            <div class="card p-3 bg-success text-white border shadow-sm rounded-3">
                                <small class="text-white-50 fw-medium">Omset Masuk (Verified)</small>
                                <h3 class="fw-bold mb-0 mt-1">{{ currencyFormat(stats.omset_lunas) }}</h3>
                                <div class="border-top border-light border-opacity-25 mt-3 pt-2">
                                    <small class="text-white-50" style="font-size: 12px;">
                                        <i class="bi bi-info-circle me-1"></i> Total akumulasi dana yang telah dibayar.
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12 col-xl-4">
                            <div class="card p-3 bg-warning-subtle text-dark border border-warning shadow-sm rounded-3">
                                <small class="text-muted fw-medium">Piutang / Menunggu Pelunasan</small>
                                <h3 class="fw-bold mb-0 mt-1 text-warning-emphasis">{{ currencyFormat(stats.piutang) }}
                                </h3>
                                <div class="border-top border-warning border-opacity-25 mt-3 pt-2">
                                    <small class="text-muted" style="font-size: 12px;">
                                        <i class="bi bi-info-circle me-1"></i> Σ (Nilai Harga Deal Keseluruhan - Dana
                                        Masuk)
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12 col-xl-4">
                            <div class="card p-3 bg-white border shadow-sm rounded-3">
                                <small class="text-muted fw-medium">Rata-rata Nilai Deal (Ticket Size)</small>
                                <h3 class="fw-bold mb-0 mt-1 text-dark">{{ currencyFormat(stats.avg_deal) }}</h3>
                                <div class="border-top mt-3 pt-2">
                                    <small class="text-muted" style="font-size: 12px;">
                                        <i class="bi bi-info-circle me-1"></i> Σ Total Nilai Harga Deal ÷ Jumlah
                                        Transaksi
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="card border-0 rounded-4 overflow-hidden">

                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Data Penjualan & Invoice</h5>
                                    <small class="text-muted">Manajemen transaksi masuk dan pelunasan tagihan
                                        pelanggan.</small>
                                </div>
                                <div class="dropdown" v-if="hasPermission('export-data')">
                                    <button data-bs-toggle="dropdown" class="btn btn-c-slate dropdown-toggle"
                                        type="button">
                                        <i class="bi bi-download me-2 "></i>
                                        Export Data
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item text-success" href="#">
                                                <i class="bi bi-file-earmark-excel-fill me-1 "></i>
                                                Unduh Excel
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#">
                                                <i class="bi bi-file-earmark-pdf-fill me-1 "></i> Unduh PDF</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="row p-3 g-2 align-items-center">
                            <div class="col-12 col-md-12 col-lg-12 col-xl-6">
                                <div class="position-relative">
                                    <i class="bi bi-search input-icon-left"></i>
                                    <input-text ref="keywordRef" :isValid="false" v-model="filters.keyword"
                                        inputClass="input-fixed-height input-height-1 rounded-pill"
                                        placeholder="Cari nama pelanggan dan nomor handphone...." />
                                </div>
                            </div>
                            <div v-if="hasRole('super_admin')" class="col-xl-1 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="branch" v-model="filters.branch"
                                    text="Pilih Cabang" :options="branchOptions" />
                            </div>
                            <div class="col-xl-1 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="limit" v-model="filters.limit"
                                    text="Pilih Batas Data" :options="[
                                        { label: '☰ 10 Data', value: 10 },
                                        { label: '☰ 25 Data', value: 25 },
                                        { label: '☰ 50 Data', value: 50 },
                                        { label: '☰ 100 Data', value: 100 },
                                    ]" />
                            </div>
                            <div class="col-xl-1 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="order_by" v-model="filters.order_by"
                                    text="Pilih Urutan" :options="[
                                        { label: '🔼 Terbaru', value: 'latest' },
                                        { label: '🔽 Terlama', value: 'oldest' }
                                    ]" />
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <table-wrapper :loader="isLoading" loaderText="Sedang memuat data..." :headers="header"
                                :items="sales?.data" rowKey="id_sales" @update:selected="(val) => selectedIdSale = val">

                                <template #empty>
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox fa-3x text-muted opacity-25 mb-3 d-block"></i>
                                        <h6 class="text-muted fw-bold">Data Penjualan Anda Masih Kosong</h6>
                                    </div>
                                </template>

                                <template #row="{ item, index }">
                                    <td class="td-number">
                                        {{ index + 1 + (sales?.current_page - 1) * sales?.per_page }}
                                    </td>

                                    <td class="ps-4">
                                        <span class="d-block fw-bold text-primary"
                                            v-html="highlight(item.invoice, filters.keyword)"></span>
                                        <span class="text-muted small">{{ formatDate(item.deal_at) }}</span>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="badge bg-primary-subtle text-primary p-2 rounded-circle fs-4">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div class="text-capitalize">
                                                <span class="fw-bold text-dark mb-0" v-html="highlight(item.lead_customer_name ||
                                                    'Unknown', filters.keyword)"></span>
                                                <div class="d-block mb-0 small fw-medium">
                                                    <i class="bi bi-whatsapp me-1"></i>
                                                    <span v-html="highlight(item.lead_customer_phone ||
                                                        'Unknown', filters.keyword)">
                                                    </span>
                                                </div>
                                                <span class="text-muted small" style="font-size: 14px;">
                                                    <i class="bi bi-box me-1"></i> {{ item.product_name || '-'
                                                    }}
                                                </span>
                                            </div>

                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <span class="fw-bold text-dark">{{ currencyFormat(item.deal_price) }}</span>
                                    </td>

                                    <td class="text-center">
                                        <div v-if="item.payment_status === 'LUNAS'"
                                            class="d-inline-flex align-items-center gap-2 ">
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> LUNAS
                                            </span>
                                        </div>

                                        <div v-else class="d-flex flex-column">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span
                                                    class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                                                    <i class="bi bi-hourglass-split me-1"></i> {{ item.payment_status }}
                                                </span>
                                                <span class="text-danger fw-bold" style="font-size: 12px;">
                                                    Sisa: {{ currencyFormat(item.remaining_bill) }}
                                                </span>
                                            </div>
                                            <div class="progress" style="height: 4px;">
                                                <div class="progress-bar bg-success"
                                                    :style="`width: ${(item.paid_amount / item.deal_price) * 100}%`">
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="align-middle text-start">
                                        <div class="d-flex align-items-center">
                                            <div
                                                class="client-avatar me-3 bg-gradient-light border text-primary fw-bold small d-flex align-items-center justify-content-center shadow-sm">
                                                {{ item.sales_name ? item.sales_name.substring(0, 2).toUpperCase()
                                                    : '??' }}
                                            </div>
                                            <div class="d-flex flex-column small" style="line-height: 1.3;">
                                                <span class="fw-bold text-dark mb-1 text-capitalize">{{
                                                    item.sales_name || 'Sistem'
                                                    }} {{ `(${getRoleName(item.role_name).label ?? 'Unknown'})` }}
                                                </span>
                                                <div class="text-muted d-flex flex-column" style="font-size: 0.7rem;">
                                                    <span><i class="fas fa-plus-circle me-1 opacity-50"></i>
                                                        {{ formatDate(item.created_at) }}
                                                    </span>
                                                    <span v-if="item.updated_at !== item.created_at">
                                                        <i class="fas fa-pencil-alt me-1 opacity-50"></i> {{
                                                            formatDate(item.updated_at)
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <action-button @cancelClosing="cancelClosing(item)" :item="item"
                                            :actions="payActions" @pay="openPaymentModal(item)"
                                            @history="openActivityModal(item)" />
                                    </td>

                                </template>


                                <template #pagination>
                                    <div
                                        class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center ">
                                        <small class="text-muted mb-2 mb-md-0">
                                            Menampilkan <strong>{{ sales.from ?? 0 }}</strong> - <strong>{{
                                                sales.to ?? 0 }}</strong> dari <strong>{{ sales.total
                                                    ?? 0
                                                }}</strong>
                                        </small>
                                        <pagination size="pagination-sm" :links="sales?.links" routeName="sales.index"
                                            :additionalQuery="{
                                                keyword: filters.keyword,
                                                limit: filters.limit,
                                                order_by: filters.order_by,
                                                branch: filters.branch,
                                            }" :only="['sales', 'filters', 'stats', 'branch']" />
                                    </div>
                                </template>


                            </table-wrapper>
                        </div>
                    </div>
                </div>

            </div>
            <PaymentModal :idSales="selectedIdSale" :show="showPaymentModal" @update:show="showPaymentModal = $event" />
            <ActivityTimelineModal :idSales="selectedIdSale" :show="showActivityModal"
                @update:show="showActivityModal = $event" />
        </template>
    </app-layout>
</template>
