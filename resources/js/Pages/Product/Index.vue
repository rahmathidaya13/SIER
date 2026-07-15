<script setup>
import { Head } from '@inertiajs/vue3';
import { useFilter } from '@/Composables/useFilter';
import { hasRole } from '@/Composables/useAuth';
import { computed, ref } from 'vue';
import { formatDate } from '@/Helpers/formatDate';
import { highlight } from '@/Helpers/Highlight';
import FormModal from './FormModal.vue';
const props = defineProps({
    products: Object,
    category: Array,
    filters: Object
});
console.log(props.products.data);
const {
    filters,
    isLoading,
} = useFilter({
    routeName: "product.index",
    initialFilters: {
        keyword: props.filters.keyword ?? '',
        limit: props.filters.limit ?? 10,
        order_by: props.filters.order_by ?? 'latest',
    },
    watchFields: [
        'keyword',
        'limit',
        'order_by',
    ],
    only: ['products', 'filters'],
    debounceTime: 500,
});
const header = [
    {
        label: "No",
        key: "__index",
        attrs: {
            class: 'text-center td-number',
        },
    },
    {
        label: "kode Produk",
        key: "code_",
    },
    {
        label: "IMAGE",
        key: "images",
    },
    {
        label: "INFO PRODUK",
        key: "product",
    },
    {
        label: "HARGA SAAT INI (Official)",
        key: "price",
        attrs: {
            class: 'text-center',
        },
    },
    {
        label: "STATUS",
        key: "status_",
        attrs: {
            class: 'text-center',
        },
    },
    {
        label: "Dibuat Oleh",
        key: "creator",
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
const showModal = ref(false);
const selectedId = ref(null);
const openCreateModal = () => {
    showModal.value = true;
    selectedId.value = null;
}
const openEditModal = (data) => {
    showModal.value = true;
    selectedId.value = data.id_product;
}


const getImageUrl = (item) => {
    if (item.source === 'scrape' && item.image_link) {
        return item.image_link; // Gambar dari URL luar
    }
    if (item.image_path) {
        return `/storage/${item.image_path}`; // Gambar lokal dari storage Laravel
    }
    return '/img/placeholder.png'; // Fallback jika tidak ada gambar
};
</script>
<template>

    <Head title="Daftar Produk" />
    <app-layout>
        <template #content>
            <div class="row g-3 view-section">
                <div class="col-12">
                    <callout />
                    <div class="card border-0 rounded-4 overflow-hidden">

                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Manajemen Produk</h5>
                                    <small class="text-muted">Buat Produk Baru atau Ubah data Produk</small>
                                </div>
                                <button @click.prevent="openCreateModal" class="btn btn-c-primary px-4 py-2">
                                    <i class="bi bi-plus-circle me-1"></i> Produk Baru
                                </button>
                            </div>
                        </div>

                        <div class="row p-3 g-2 align-items-center">
                            <div class="col-12 col-md-12 col-lg-12 col-xl-6">
                                <div class="position-relative">
                                    <i class="bi bi-search input-icon-left"></i>
                                    <input-text ref="keywordRef" :isValid="false" v-model="filters.keyword"
                                        inputClass="input-fixed-height input-height-1 rounded-pill"
                                        placeholder="Cari produk dan kode produk...." />
                                </div>
                            </div>
                            <div class="col-xl-2 col-6 col-md-6 col-lg-6">
                                <select-input :isValid="false" name="limit" v-model="filters.limit"
                                    text="Pilih Batas Data" :options="[
                                        { label: '☰ 10 Data', value: 10 },
                                        { label: '☰ 25 Data', value: 25 },
                                        { label: '☰ 50 Data', value: 50 },
                                        { label: '☰ 100 Data', value: 100 },
                                    ]" />
                            </div>
                            <div class="col-xl-2 col-6 col-md-6 col-lg-6">
                                <select-input :isValid="false" name="order_by" v-model="filters.order_by"
                                    text="Pilih Urutan" :options="[
                                        { label: '🔼 Terbaru', value: 'latest' },
                                        { label: '🔽 Terlama', value: 'oldest' }
                                    ]" />
                            </div>

                        </div>

                        <div class="card-body p-0">
                            <table-wrapper :loader="isLoading" loaderText="Sedang memuat data..." :headers="header"
                                :items="products?.data" rowKey="id_product"
                                @update:selected="(val) => selectedId = val">

                                <template #empty>
                                    <div class="text-center py-5">
                                        <i class="bi bi-funnel fa-3x text-muted opacity-25 mb-3 d-block"></i>
                                        <h6 class="text-muted fw-bold">Data produk Masih Kosong</h6>
                                        <p class="text-muted small mb-0">Klik tombol "Buat produk baru" di pojok kanan
                                            atas untuk membuat produk.</p>
                                    </div>
                                </template>

                                <template #row="{ item, index }">
                                    <td class="td-number">
                                        {{ index + 1 + (products?.current_page - 1) * products?.per_page }}
                                    </td>
                                    <td class="text-center">
                                        <div class="badge badge-soft-slate rounded-4"
                                            v-html="highlight(item.code, filters.keyword)">
                                        </div>
                                    </td>
                                    <td class="ps-4">
                                        <img :src="getImageUrl(item)"
                                            class="rounded-3 object-fit-cover border shadow-sm" width="56" height="56"
                                            alt="Produk">
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark mb-1"
                                            v-html="highlight(item.product_name, filters.keyword)"></div>
                                        <div class="d-block">
                                            <div class="gap-2 d-flex mb-1">
                                                <div class="badge bg-secondary bg-opacity-10 text-secondary border">
                                                    <i class="fas fa-tags me-1"></i>
                                                    <span v-html="highlight(item.category, filters.keyword)"></span>
                                                </div>
                                                <span
                                                    class="badge bg-info bg-opacity-10 text-info border text-capitalize">
                                                    {{ item.condition }}
                                                </span>
                                            </div>
                                            <a v-if="item.link" :href="item.link" class="small" target="_blank">
                                                <i class="bi bi-arrow-up-right me-1"></i>
                                                Klik link untuk melihat produk</a>
                                        </div>
                                    </td>

                                    <td>
                                        <div v-if="item.base_price">
                                            <div v-if="item.discount_price > 0">
                                                <div class="fw-bold text-success">
                                                    Rp {{ item.discount_price.toLocaleString('id-ID') }}
                                                </div>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <small class="text-muted text-decoration-line-through">
                                                        Rp {{ item.base_price.toLocaleString('id-ID') }}
                                                    </small>
                                                    <span class="badge bg-danger rounded-pill px-2 py-1"
                                                        style="font-size: 10px;">
                                                        -{{ item.discount_percentage }}%
                                                    </span>
                                                </div>
                                            </div>
                                            <div v-else class="fw-bold text-dark">
                                                Rp {{ item.base_price.toLocaleString('id-ID') }}
                                            </div>
                                        </div>

                                        <div v-else class="text-muted small fst-italic">
                                            <i class="bi bi-exclamation-circle text-warning me-1"></i> Harga belum
                                            diset/Expired
                                        </div>
                                    </td>

                                    <td>
                                        <span v-if="item.status === 'published'"
                                            class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2 rounded-pill">
                                            <i class="bi bi-check-circle me-1"></i> Published
                                        </span>
                                        <span v-else
                                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">
                                            <i class="bi bi-archive me-1"></i> Draft
                                        </span>
                                    </td>

                                    <td class="align-middle text-start">
                                        <div class="d-flex align-items-center">
                                            <div
                                                class="client-avatar me-3 bg-gradient-light border text-primary fw-bold small d-flex align-items-center justify-content-center shadow-sm">
                                                {{ item.creator_name ? item.creator_name.substring(0, 2).toUpperCase()
                                                    : 'N/A' }}
                                            </div>
                                            <div class="d-flex flex-column small" style="line-height: 1.3;">
                                                <span class="fw-bold text-dark mb-1 text-capitalize">{{
                                                    item.creator_name || 'N/A'
                                                }}
                                                </span>
                                                <div class="text-muted d-flex flex-column" style="font-size: 0.7rem;">
                                                    <span><i class="fas fa-plus-circle me-1 opacity-50"></i>
                                                        {{ formatDate(item.created_at)
                                                        }}
                                                    </span>
                                                    <span v-if="item.updated_at !== item.created_at">
                                                        <i class="fas fa-pencil-alt me-1 opacity-50"></i> {{
                                                            formatDate(item.updated_at) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="pe-4 text-end">
                                        <button @click.prevent="openEditModal(item)" type="button"
                                            class="btn btn-c-slate" title="Edit Produk">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    </td>

                                </template>


                                <template #pagination>
                                    <div
                                        class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center ">
                                        <small class="text-muted mb-2 mb-md-0">
                                            Menampilkan <strong>{{ products.from ?? 0 }}</strong> - <strong>{{
                                                products.to ?? 0 }}</strong> dari <strong>{{ products.total
                                                    ?? 0
                                                }}</strong>
                                        </small>
                                        <pagination size="pagination-sm" :links="products?.links"
                                            routeName="product.index" :additionalQuery="{
                                                keyword: filters.keyword,
                                                limit: filters.limit,
                                                order_by: filters.order_by,
                                            }" :only="['products', 'filters']" />
                                    </div>
                                </template>


                            </table-wrapper>
                        </div>
                    </div>
                </div>
            </div>
            <FormModal :category="props.category" :idProduct="selectedId" :show="showModal"
                @update:show="showModal = $event" />
        </template>
    </app-layout>
</template>
