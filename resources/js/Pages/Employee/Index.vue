<script setup>
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useFilter } from '@/Composables/useFilter';
import { highlight } from "@/Helpers/Highlight";
import { useConfirm } from "@/Composables/useConfirm"
import { formatDate } from '@/Helpers/formatDate';
import FormModal from './FormModal.vue';
const confirm = useConfirm();
const props = defineProps({
    employee: Object,
    branches: Array,
    filters: Object,
})

const showModal = ref(false);
const selectedByIdUser = ref(null);
const openCreateModal = () => {
    showModal.value = true;
    selectedByIdUser.value = null;
}
const openEditModal = (data) => {
    showModal.value = true;
    selectedByIdUser.value = data.id;
}
const getRoleBadgeClass = (roleName) => {
    const roles = {
        'developer': {
            label: 'Developer',
            color: 'bg-dark text-white'
        },
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
const getInitials = (name) => {
    return name.match(/(\b\S)?/g).join('').match(/(^\S|\S$)?/g).join('').toUpperCase();
};
const {
    filters,
    isLoading,
} = useFilter({
    routeName: "employee.index",
    initialFilters: {
        keyword: props.filters.keyword ?? '',
        limit: props.filters.limit ?? 10,
        status: props.filters.status ?? 'all',
        sort_order: props.filters.sort_order ?? 'latest',
        page: props.filters.page ?? 1,
    },
    watchFields: [
        'keyword',
        'limit',
        'status',
        'sort_order',
    ],
    only: ['employee', 'filters', 'branches'],
    debounceTime: 500,
});

const header = [
    {
        label: "No",
        key: "__index",
        attrs: {
            class: 'ps-4 py-3 font-medium td-number',
        },
    },
    {
        label: "INFORMASI PEGAWAI",
        key: "name",
        attrs: {
            style: {
                width: '40%',
            },
            class: 'ps-4 py-3 font-medium',
        },
    },
    {
        label: "HAK AKSES / ROLE",
        key: "roles",
        attrs: {
            class: 'py-3 font-medium',
        },
    },
    {
        label: "PENEMPATAN CABANG",
        key: "branch",
        attrs: {
            class: 'py-3 font-medium',
        },
    },
    {
        label: "STATUS PEGAWAI",
        key: "status",
        attrs: {
            class: 'py-3 font-medium',
        },
    },
    {
        label: "TGL BERGABUNG",
        key: "price",
        attrs: {
            class: 'py-3 font-medium text-center',
        },
    },

    {
        label: "Aksi",
        key: "actions",
        attrs: {
            style:
                { width: '5%' },
            class: 'pe-4 py-3 font-medium text-center'
        },
    },
];
const employeeActions = [
    {
        label: 'Ubah Pegawai',
        action: 'edit',
        protectedSelf: true,
        icon: 'bi bi-pencil-square',
        role: ['developer', 'super_admin', 'supervisor'],
        title: 'Ubah Pegawai',
        buttonClass: 'btn-action',
        variant: 'edit',
    },
    {
        label: 'Hapus Pegawai',
        action: 'delete',
        protectedSelf: true,
        icon: 'bi bi-trash',
        buttonClass: 'btn-action',
        variant: 'delete',
        title: 'Hapus Produk',
        role: ['developer', 'super_admin'],

    },
];
const deleted = async (nameRoute, data) => {
    const setConfirm = await confirm.ask({
        title: 'Hapus',
        message: `Kamu ingin menghapus Data Pegawai ${data.name} ?`,
        confirmText: 'Ya, Hapus',
        variant: 'danger'
    });

    if (setConfirm) {
        // loaderActive.value?.show("Sedang menghapus data...");
        router.delete(route(nameRoute, data.id), {
            preserveScroll: false,
            replace: true,
            preserveState: true
        });
    }
}
const keywordRef = ref(null);
onMounted(() => {
    keywordRef.value.focus();
})
</script>
<template>

    <Head title="Halaman Data Pegawai" />
    <app-layout>
        <template #content>
            <div class="row g-3 view-section">
                <div class="col-12">
                    <callout :duration="0" />
                    <div class="card border-0 rounded-4 overflow-hidden">

                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Manajemen Pegawai</h5>
                                    <small class="text-muted">Kelola akses portal dan penempatan cabang
                                        internal.</small>
                                </div>
                                <button @click.prevent="openCreateModal" class="btn btn-c-primary px-4 py-2">
                                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Pegawai
                                </button>
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
                            <div class="col-xl-1 col-4 col-md-4 col-lg-4">
                                <select-input :isValid="false" name="limit" v-model="filters.limit"
                                    text="Pilih Batas Data" :options="[
                                        { label: '10 Data', value: 10 },
                                        { label: '25 Data', value: 25 },
                                        { label: '50 Data', value: 50 },
                                        { label: '100 Data', value: 100 },
                                    ]" />
                            </div>
                            <div class="col-xl-1 col-4 col-md-4 col-lg-4">
                                <select-input :isValid="false" name="sort_order" v-model="filters.sort_order"
                                    text="Pilih Urutan" :options="[
                                        { label: 'Terbaru', value: 'latest' },
                                        { label: 'Terlama', value: 'oldest' }
                                    ]" />
                            </div>
                            <div class="col-xl-1 col-4 col-md-4 col-lg-4">
                                <select-input :isValid="false" name="status" v-model="filters.status" text="Status Akun"
                                    :options="[
                                        { label: 'Semua Status Akun', value: 'all' },
                                        { label: 'Aktif', value: 'active' },
                                        { label: 'Tidak Aktif', value: 'inactive' }
                                    ]" />
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <table-wrapper :loader="isLoading" loaderText="Sedang memuat data..." :headers="header"
                                :items="employee?.data" row-key="product_id"
                                @update:selected="(val) => selectedByIdUser = val">

                                <template #empty>
                                    <i class="bi bi-person-circle fa-3x text-muted opacity-25 mb-3"></i>
                                    <p class="text-muted fw-semibold">Tidak ada pegawai yang ditemukan</p>
                                </template>

                                <template #row="{ item, index }">
                                    <td class="td-number">
                                        {{ index + 1 + (employee?.current_page - 1) * employee?.per_page }}
                                    </td>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="client-avatar bg-primary-subtle text-primary rounded-circle">
                                                {{ getInitials(item.name) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark text-capitalize"
                                                    v-html="highlight(item.name, filters.keyword)"></h6>
                                                <span class="text-muted small">{{ item.email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span v-if="item.role_name" class="badge rounded-pill px-3 py-2 fw-medium"
                                            :class="getRoleBadgeClass(item.role_name).color">
                                            <i class="bi bi-shield-check me-1 opacity-75"></i> {{
                                                getRoleBadgeClass(item.role_name).label
                                            }}
                                        </span>
                                        <span v-else class="text-muted small"><i>Tanpa Akses</i></span>
                                    </td>

                                    <td class="py-3">
                                        <div v-if="item.branch_name"
                                            class="d-flex align-items-center gap-2 text-dark fw-medium small">
                                            <i class="bi bi-geo-alt-fill text-danger"></i> {{ item.branch_name
                                            }}
                                        </div>
                                        <div v-else class="d-flex align-items-center gap-2 text-primary fw-bold small">
                                            <i class="bi bi-building"></i> Kantor Pusat
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div style="font-size: 13px;"
                                            :class="`badge px-5 rounded-pill fw-medium ${item.is_active === 'active' ? 'badge-soft-success' : 'badge-soft-danger'}`">
                                            <i
                                                :class="` ${item.is_active === 'active' ? 'bi bi-unlock-fill' : 'bi bi-lock-fill'}`"></i>
                                            {{ item.is_active === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                                        </div>
                                    </td>

                                    <td class="py-3 text-center text-muted small">
                                        {{ formatDate(item.created_at) }}
                                    </td>
                                    <td>
                                        <action-button :item="item" :actions="employeeActions"
                                            @edit="openEditModal(item)" @delete="deleted('employee.delete', item)" />
                                    </td>
                                </template>
                                <template #pagination>
                                    <div
                                        class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center ">
                                        <small class="text-muted mb-2 mb-md-0">
                                            Menampilkan <strong>{{ employee.from ?? 0 }}</strong> - <strong>{{
                                                employee.to ?? 0 }}</strong> dari <strong>{{ employee.total
                                                    ?? 0
                                                }}</strong>
                                        </small>
                                        <pagination size="pagination-sm" :links="employee?.links"
                                            routeName="employee.index" :additionalQuery="{
                                                keyword: filters.keyword,
                                                status: filters.status,
                                                limit: filters.limit,
                                                sort_order: filters.sort_order,
                                            }" :only="['employee', 'filters', 'branches']" />
                                    </div>
                                </template>
                            </table-wrapper>
                        </div>
                    </div>
                </div>
            </div>
            <FormModal :idUser="selectedByIdUser" :branches="props.branches" :show="showModal"
                @update:show="showModal = $event" />
        </template>
    </app-layout>
</template>
