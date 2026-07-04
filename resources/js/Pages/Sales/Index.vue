<script setup>
import { Form, Head, usePage, router, Link } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import { useFilter } from '@/Composables/useFilter';
import { useConfirm } from "@/Composables/useConfirm"
import { highlight } from "@/Helpers/Highlight";
import axios from 'axios';
import { formatDate } from '@/Helpers/formatDate';
import { currencyFormat } from '@/Helpers/currencyFormat';
const props = defineProps({
    sales: Object,
    filters: Object,
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
        page: props.filters.page ?? 1,
    },
    watchFields: [
        'keyword',
        'limit',
        'order_by',
    ],
    only: ['leads', 'filters'],
    debounceTime: 500,
});

const showPaymentModal = ref(false);
const selectedIdSale = ref(null);

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
            class: 'text-center',
        },
    },
    {
        label: "HARGA DEAL",
        key: "deal_prices",
        attrs: {
            style: {
                width: '15%'
            },
            class: 'text-center',
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
console.log(props.sales.data);
</script>
<template>

    <Head title="Rekap Closing" />
    <app-layout>
        <template #content>
            <div class="row g-3">
                <div class="col-12">
                    <callout />
                    <div class="card border-0 rounded-4 overflow-hidden">

                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Data Penjualan & Invoice</h5>
                                    <small class="text-muted">Manajemen transaksi masuk dan pelunasan tagihan
                                        pelanggan.</small>
                                </div>
                                <!-- <button @click.prevent="openCreateModal" class="btn btn-c-primary px-4 py-2">
                                    <i class="bi bi-plus-circle me-1"></i> Lead Baru
                                </button> -->
                            </div>
                        </div>
                        <div class="row p-3 g-2 align-items-center">
                            <div class="col-12 col-md-12 col-lg-12 col-xl-6">
                                <div class="position-relative">
                                    <i class="bi bi-search input-icon-left"></i>
                                    <input-text ref="keywordRef" :isValid="false" v-model="filters.keyword"
                                        inputClass="input-fixed-height input-height-1 rounded-pill"
                                        placeholder="Cari nama...." />
                                </div>
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
                                        <span class="d-block fw-bold text-dark">{{ item.invoice }}</span>
                                        <span class="text-muted small">{{ formatDate(item.deal_at) }}</span>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div class="text-capitalize">
                                                <span class="d-block fw-bold text-dark mb-0">{{
                                                    item.lead_customer_name ||
                                                    'Unknown' }}</span>
                                                <span class="text-muted small" style="font-size: 11px;">
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
                                        <!-- Jika LUNAS -->
                                        <div v-if="item.payment_status === 'LUNAS'"
                                            class="d-inline-flex align-items-center gap-2 ">
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> LUNAS
                                            </span>
                                        </div>

                                        <!-- Jika DP / TERMIN (Masih Punya Sisa Tagihan) -->
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
                                                <!-- Visualisasi Progress Bar Uang Masuk -->
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
                                                    }}</span>
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
                                        <div class="d-flex justify-content-center gap-2">
                                            <!-- Tombol Invoice (Selalu Muncul) -->
                                            <button class="btn btn-light btn-sm border shadow-sm text-primary"
                                                title="Lihat Invoice">
                                                <i class="bi bi-receipt"></i>
                                            </button>

                                            <!-- TOMBOL SAKTI: Pelunasan (Hanya muncul jika bukan Lunas) -->
                                            <!-- <button v-if="sale.payment_status !== 'LUNAS' && sale.remaining_bill > 0"
                                                @click="openPaymentModal(sale)"
                                                class="btn btn-c-success btn-sm shadow-sm fw-medium d-flex align-items-center gap-1">
                                                <i class="bi bi-wallet2"></i> Tagih
                                            </button> -->
                                        </div>
                                    </td>

                                    <!-- <td class="text-center">
                                        <action-button :item="item" :actions="leadActions" @edit="openEditModal(item)"
                                            @delete="deleted('sales.delete', item)" @deals="openConvertModal(item)" />
                                    </td> -->

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
                                            }" :only="['sales', 'filters']" />
                                    </div>
                                </template>


                            </table-wrapper>
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </app-layout>
</template>
