<script setup>
import { Form, Head, usePage, router, Link } from "@inertiajs/vue3";
import { computed, onMounted, reactive, ref } from "vue";
import { useFilter } from "@/Composables/useFilter";
import { useConfirm } from "@/Composables/useConfirm";
import { highlight } from "@/Helpers/Highlight";
import { formatDate } from "@/Helpers/formatDate";
import { currencyFormat } from "@/Helpers/currencyFormat";
import { hasRole } from "@/Composables/useAuth";
import FormModal from "./FormModal.vue";
const confirm = useConfirm();

const props = defineProps({
    branches: Object,
    filters: Object,
});
console.log(props.branches.data);
const showModal = ref(false);
const selectedData = ref(null);

const openCreateModal = () => {
    showModal.value = true;
    selectedData.value = null;
};
const openEditModal = async (data) => {
    showModal.value = true;
    selectedData.value = data.id_branch;
};
const { filters, isLoading } = useFilter({
    routeName: "branch.index",
    initialFilters: {
        keyword: props.filters.keyword ?? "",
        limit: props.filters.limit ?? 10,
        order_by: props.filters.order_by ?? "latest",
        page: props.filters.page ?? 1,
    },
    watchFields: ["keyword", "limit", "order_by"],
    only: ["branches", "filters"],
    debounceTime: 500,
});
const header = [
    {
        label: "No",
        key: "__index",
        attrs: {
            class: "text-center td-number",
        },
    },
    {
        label: "INFORMASI CABANG",
        key: "branch",
        attrs: {
            style: { width: "20%" },
        },
    },
    {
        label: "KONTAK",
        key: "contact",
        attrs: {
            style: { width: "20%" },
        },
    },
    {
        label: "LOKASI",
        key: "location",
        attrs: {
            style: "25%",
        },
    },
    {
        label: "PEGAWAI",
        key: "employee",
        attrs: {
            class: "text-center",
        },
    },
    {
        label: "STATUS",
        key: "status",
        attrs: {
            class: "text-center",
        },
    },
    {
        label: "Aksi",
        key: "actions",
        attrs: {
            class: "text-center",
        },
    },
];
const deleted = async (nameRoute, data) => {

    const setConfirm = await confirm.ask({
        title: 'Hapus',
        message: `Kamu ingin menghapus Cabang ${data.name} ini ? Data yang sudah dihapus tidak dapat dikembalikan!`,
        confirmText: 'Ya, Hapus',
        variant: 'danger'
    });

    if (setConfirm) {
        router.delete(route(nameRoute, data.id_branch), {
            preserveScroll: true,
            replace: true,
            preserveState: true
        });
    }
}
const keywordRef = ref(null);
onMounted(() => {
    if (keywordRef.value) {
        keywordRef.value.focus();
    }
});
</script>
<template>

    <Head title="Halaman Data Cabang" />
    <app-layout>
        <template #content>
            <div class="row g-3 view-section">
                <callout />
                <div class="col-12">
                    <div class="card border-0 rounded-4 overflow-hidden">
                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Daftar Cabang</h5>
                                    <small class="text-muted">Manajemen cabang internal</small>
                                </div>
                                <button @click.prevent="openCreateModal" class="btn btn-c-primary px-4 py-2">
                                    <i class="bi bi-plus-circle me-1"></i> Cabang Baru
                                </button>
                            </div>
                        </div>

                        <div class="row p-3 g-2 align-items-center">
                            <div class="col-12 col-md-12 col-lg-12 col-xl-6">
                                <div class="position-relative">
                                    <i class="bi bi-search input-icon-left"></i>
                                    <input-text ref="keywordRef" :isValid="false" v-model="filters.keyword"
                                        inputClass="input-fixed-height input-height-1 rounded-pill"
                                        placeholder="Cari cabang dan nomor telepon...." />
                                </div>
                            </div>
                            <div class="col-xl-1 col-6 col-md-6 col-lg-6">
                                <select-input :isValid="false" name="limit" v-model="filters.limit"
                                    text="Pilih Batas Data" :options="[
                                        { label: '☰ 10 Data', value: 10 },
                                        { label: '☰ 25 Data', value: 25 },
                                        { label: '☰ 50 Data', value: 50 },
                                        { label: '☰ 100 Data', value: 100 },
                                    ]" />
                            </div>
                            <div class="col-xl-1 col-6 col-md-6 col-lg-6">
                                <select-input :isValid="false" name="sort_order" v-model="filters.order_by"
                                    text="Pilih Urutan" :options="[
                                        { label: '🔼 Terbaru', value: 'latest' },
                                        { label: '🔽 Terlama', value: 'oldest' }
                                    ]" />
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <table-wrapper :loader="isLoading" loaderText="Sedang memuat data..." :headers="header"
                                :items="branches?.data" row-key="id_branch"
                                @update:selected="(val) => selectedData = val">

                                <template #empty>
                                    <div
                                        class="d-flex flex-column align-items-center justify-content-center text-muted">
                                        <i class="bi bi-inboxes display-4 text-secondary opacity-50 mb-3"></i>
                                        <h6 class="fw-semibold">Tidak ada data cabang</h6>
                                        <small>Data cabang yang Anda cari tidak ditemukan atau belum
                                            ditambahkan.</small>
                                    </div>
                                </template>

                                <template #row="{ item, index }">
                                    <td class="td-number">
                                        {{ index + 1 + (branches?.current_page - 1) * branches?.per_page }}
                                    </td>

                                    <td class="text-start">
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark mb-1" style="font-size: 14px;"
                                                v-html="highlight(item.name, filters.keyword)"></span>
                                            <div class="d-flex align-items-center gap-2">
                                                <span
                                                    class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"
                                                    style="font-size: 10px; letter-spacing: 0.5px;">
                                                    <i class="bi bi-hash"></i>{{ item.branch_code }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-start">
                                        <div v-if="item.branch_phone && item.branch_phone.length > 0"
                                            class="d-flex flex-column gap-1">
                                            <div v-for="(phone, pIndex) in item.branch_phone.slice(0, 2)" :key="pIndex"
                                                class="d-flex align-items-center text-muted fw-medium"
                                                style="font-size: 12px;">
                                                <i class="bi bi-telephone-fill me-2 text-primary"
                                                    style="font-size: 12px;"></i>
                                                <span v-html="highlight(phone.phone, filters.keyword)"></span>
                                            </div>

                                            <div @click="openEditModal(item)" v-if="item.branch_phone.length > 2"
                                                class="text-primary fw-bold ms-3 cursor-pointer"
                                                style="font-size: 12px;"
                                                :title="'Ada ' + (item.branch_phone.length - 2) + ' nomor lain'">
                                                +{{ item.branch_phone.length - 2 }} nomor lainnya
                                            </div>
                                        </div>
                                        <div v-else>
                                            <span class="text-muted" style="font-size: 12px; font-style: italic;">Belum
                                                ada kontak</span>
                                        </div>
                                    </td>

                                    <td class="text-start">
                                        <div class="text-muted" style="font-size: 14px; overflow: hidden;"
                                            :title="item.address">
                                            <i class="bi bi-geo-alt me-1"></i> {{ item.address || '-' }}
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center px-2 py-1 bg-info-subtle text-info-emphasis rounded-2 border border-info-subtle"
                                            style="font-size: 11.5px; font-weight: 600;">
                                            <i class="bi bi-people-fill me-1"></i>
                                            {{ item.users_count || 0 }} Karyawan
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge py-1 px-2 border"
                                            :class="item.is_active === 'active' ? 'bg-success-subtle text-success border-success-subtle' : 'bg-danger-subtle text-danger border-danger-subtle'"
                                            style="font-size: 11px;">
                                            <i class="bi me-1"
                                                :class="item.is_active === 'active' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"></i>
                                            {{ item.is_active === 'active' ? 'Aktif' : 'Non-Aktif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <button @click="openEditModal(item)" class="btn btn-c-slate"
                                                title="Edit Data">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button @click="deleted('branch.delete', item)" class="btn btn-c-danger"
                                                title="Edit Data">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>

                                </template>
                                <template #pagination>
                                    <div
                                        class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center ">
                                        <small class="text-muted mb-2 mb-md-0">
                                            Menampilkan <strong>{{ branches.from ?? 0 }}</strong> - <strong>{{
                                                branches.to ?? 0 }}</strong> dari <strong>{{ branches.total
                                                    ?? 0
                                                }}</strong>
                                        </small>
                                        <pagination size="pagination-sm" :links="branches?.links"
                                            routeName="branch.index" :additionalQuery="{
                                                keyword: filters.keyword,
                                                limit: filters.limit,
                                                order_by: filters.order_by,
                                            }" :only="['filters', 'branches']" />
                                    </div>
                                </template>
                            </table-wrapper>
                        </div>
                    </div>
                </div>
                <FormModal :idBranch="selectedData" :show="showModal" @update:show="showModal = $event" />
            </div>
        </template>
    </app-layout>
</template>
