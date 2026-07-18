<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useFilter } from '@/Composables/useFilter';
import { useConfirm } from "@/Composables/useConfirm"
import { highlight } from "@/Helpers/Highlight";
import { formatDate } from '@/Helpers/formatDate';
import { hasRole, hasPermission } from "@/Composables/useAuth";
import FormModal from './FormModal.vue';
import ConvertLeadModal from './ConvertLeadModal.vue';
import ActivityTimelineModal from './ActivityTimelineModal.vue';
const confirm = useConfirm();
const props = defineProps({
    leads: Object,
    filters: Object,
    channels: Array,
    products: Array,
    role_name: String
});

const getCurrentMonth = () => {
    const today = new Date();
    const year = today.getFullYear();

    const month = String(today.getMonth() + 1).padStart(2, '0');
    return `${year}-${month}`;
}
const {
    filters,
    isLoading,
} = useFilter({
    routeName: "leads.index",
    initialFilters: {
        keyword: props.filters.keyword ?? '',
        limit: props.filters.limit ?? 10,
        status: props.filters.status ?? 'active',
        periode: props.filters.periode ?? getCurrentMonth(),
        order_by: props.filters.order_by ?? 'latest',
        page: props.filters.page ?? 1,
    },
    watchFields: [
        'keyword',
        'limit',
        'periode',
        'status',
        'order_by',
    ],
    only: ['leads', 'filters', 'channels', 'products'],
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
        label: "PROSPEK & KONTAK",
        key: "prospect",
    },
    {
        label: "SUMBER & PAKET",
        key: "source",
    },
    {
        label: "SUHU",
        key: "temperature",
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
        label: "JADWAL FOLL-UP",
        key: "price",
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
const showConvertModal = ref(false);
const openCreateModal = () => {
    showModal.value = true;
    selectedId.value = null;
}
const openConvertModal = (data) => {
    showConvertModal.value = true;
    selectedId.value = data.id_lead
}
const openEditModal = async (data) => {
    showModal.value = true;
    selectedId.value = data.id_lead
}
const handleLeadSaved = (payload) => {
    // Jika data yang baru disimpan (baik baru/edit) memiliki status 'CLOSED_WON' (Closing)
    if (payload.status === 'CLOSED_WON' && payload.temperature === 'HOT' && payload.id_lead) {

        // Beri jeda 300ms agar animasi Modal lama menutup dulu dengan mulus, baru buka modal baru
        setTimeout(() => {
            selectedId.value = payload.id_lead; // Suntikkan ID baru ke state
            showConvertModal.value = true;          // BUM! Modal Closing otomatis mekar!
        }, 300);

    }
};
const keywordRef = ref(null);
onMounted(() => {
    keywordRef.value.focus();
})

const isOverdue = (dateString, status) => {
    if (!dateString || status === 'CLOSED_WON' || status === 'CLOSED_LOST') return false;
    const today = new Date().setHours(0, 0, 0, 0);
    const follDate = new Date(dateString).setHours(0, 0, 0, 0);
    return follDate <= today;
};
const getWaLink = (phone) => {
    let p = phone.replace(/\D/g, ''); // Hapus spasi, strip, tanda plus
    if (p.startsWith('0')) p = '62' + p.slice(1);
    return `https://wa.me/${p}`;
};
const getEmail = (email) => {
    return `mailto:${email}`;
}
const getTempBadge = (temp) => {
    const map = {
        'HOT': {
            class: 'badge-soft-success border border-success',
            label: '🔥 HOT',
            title: 'Suhu Hot menandakan bahwa prospek sudah siap bayar'
        },
        'WARM': {
            class: 'badge-soft-warning border border-warning',
            label: '⏳ WARM',
            title: 'Suhu Warm menandakan bahwa pelanggan bertanya-tanya tentang detail produk'
        },
        'COLD': {
            class: 'badge-soft-info border border-info',
            label: '❄️ COLD',
            title: 'Suhu Cold menandakan bahwa pelanggan hanya sekedar ingin kepoin produk'
        }
    };
    return map[temp] || { class: 'badge-soft-dark', label: temp, title: temp };
};
const getStatusBadge = (status) => {
    const map = {
        'NEW': {
            class: 'badge-soft-primary border border-primary',
            label: '⭐ Baru Masuk'
        },
        'FOLLOW_UP': {
            class: 'badge-soft-info border border-info',
            label: '💬 Follow Up'
        },
        'CLOSED_WON': {
            class: 'badge-soft-success border border-success',
            label: '✔ CLOSING (Terjual)'

        },
        'CLOSED_LOST': {
            class: 'badge-soft-dark border border-danger',
            label: '✖ Batal / Lost'
        }
    };
    return map[status] || { class: 'badge-soft-dark', label: status };
};
const rowClasses = (item) => ({
    'tr-highlight-primary': item.status === 'NEW',
    'tr-dimmed': item.status === 'CLOSED_LOST',
    // 'tr-highlight-success': item.status === 'CLOSED_WON',
    'tr-highlight-warning': isOverdue(item.follow_up_date, item.status),
})

const deleted = async (nameRoute, data) => {
    const setConfirm = await confirm.ask({
        title: 'Hapus',
        message: `Kamu ingin menghapus Data Lead dari ${data.name} ? Data yang sudah dihapus tidak dapat dikembalikan!`,
        confirmText: 'Ya, Hapus',
        variant: 'danger'
    });

    if (setConfirm) {
        router.delete(route(nameRoute, data.id_lead), {
            preserveScroll: true,
            replace: true,
            preserveState: true
        });
    }
}
const showHistoryModal = ref(false);
const openHistoryModal = (data) => {
    selectedId.value = data.id_lead;
    showHistoryModal.value = true
}

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
const canAccess = (permission, role = []) => {
    return hasRole(role) || hasPermission(permission);
}
</script>
<template>

    <Head title="Data Rekap Leads" />
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
                                    <h5 class="fw-bold mb-1">Data Prospek (Leads)</h5>
                                    <small class="text-muted">Prospek yang belum melakukan pembayaran deal</small>
                                </div>
                                <button v-if="hasPermission('lead-create')" @click.prevent="openCreateModal"
                                    class="btn btn-c-primary px-4 py-2">
                                    <i class="bi bi-plus-circle me-1"></i> Lead Baru
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
                            <div class="col-xl-2 col-3 col-md-3 col-lg-3">
                                <input-text :isValid="false" type="month" name="periode" v-model="filters.periode" />
                            </div>
                            <div class="col-xl-2 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="status" v-model="filters.status"
                                    text="Status Prospek" :options="[
                                        { label: '📋 Semua Riwayat Data', value: 'all' },
                                        { label: '🔥 Prospek Aktif (Baru Masuk & Follow Up)', value: 'active' },
                                        { label: '🆕 Hanya Baru Masuk', value: 'new' },
                                        { label: '📞 Hanya Follow Up', value: 'follow_up' },
                                        { label: '🎉 Closing / Terjual', value: 'closed_won' },
                                        { label: '❌ Tidak Jadi', value: 'closed_lost' }
                                    ]" />
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
                                :rowClass="rowClasses" :items="leads?.data" rowKey="id_lead"
                                @update:selected="(val) => selectedId = val">

                                <template #empty>
                                    <div class="text-center py-5">
                                        <i class="bi bi-funnel fa-3x text-muted opacity-25 mb-3 d-block"></i>
                                        <h6 class="text-muted fw-bold">Data leads Anda Masih Kosong</h6>
                                        <p class="text-muted small mb-0">Klik tombol "Tambah Lead Baru" di pojok kanan
                                            atas untuk memasukkan lead pertama.</p>
                                    </div>
                                </template>

                                <template #row="{ item, index }">
                                    <td class="td-number">
                                        {{ index + 1 + (leads?.current_page - 1) * leads?.per_page }}
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark mb-1"
                                            v-html="highlight(item.name, filters.keyword)"></div>
                                        <div v-if="item.email" class="text-muted small mb-2">
                                            <a :href="getEmail(item.email)" class="text-decoration-none">{{ item.email
                                                }}</a>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <a :href="getWaLink(item.phone)" target="_blank"
                                                class="badge bg-success text-white text-decoration-none px-2 py-1 d-inline-flex align-items-center gap-1"
                                                title="Chat langsung via WhatsApp">
                                                <i class="bi bi-whatsapp"></i> {{ item.phone }}
                                            </a>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2 fw-medium text-dark small">
                                            <i class="bi fs-5" :class="item.icon || 'bi-broadcast'"></i>
                                            {{ item.channel_name }}
                                        </div>
                                        <div class="text-muted" style="font-size: 14px;">
                                            📦 {{ item.product_name || 'Belum pilih paket' }}
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge rounded-pill px-3 py-1 fw-bold"
                                            :title="getTempBadge(item.temperature).title"
                                            :class="getTempBadge(item.temperature).class">
                                            {{ getTempBadge(item.temperature).label }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge rounded-pill px-3 py-1 border-1"
                                            :title="getStatusBadge(item.status).label"
                                            :class="getStatusBadge(item.status).class">
                                            {{ getStatusBadge(item.status).label }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div v-if="item.follow_up_date" class="small fw-medium align-items-center"
                                            :class="isOverdue(item.follow_up_date, item.status) ? 'text-danger fw-bold animate-pulse' : 'text-muted fw-medium'">
                                            <i class="bi bi-calendar-event me-2"></i>
                                            <i v-if="isOverdue(item.follow_up_date, item.status)"
                                                class="bi bi-exclamation-circle-fill me-1" title="Telat dihubungi!"></i>
                                            {{ formatDate(item.follow_up_date) }}
                                        </div>
                                        <span v-else class="text-muted small">-</span>
                                    </td>

                                    <td class="align-middle text-start">
                                        <div class="d-flex align-items-center">
                                            <div
                                                class="client-avatar me-3 bg-gradient-light border text-primary fw-bold small d-flex align-items-center justify-content-center shadow-sm">
                                                {{ item.creator_name ? item.creator_name.substring(0, 2).toUpperCase()
                                                    : '??' }}
                                            </div>
                                            <div class="d-flex flex-column small" style="line-height: 1.3;">
                                                <span class="fw-bold text-dark mb-1 text-capitalize">{{
                                                    item.creator_name || 'Sistem'
                                                }} {{ `(${getRoleName(item.role_name).label})` }}
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

                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">

                                            <button @click="openHistoryModal(item)" class="btn btn-c-slate"
                                                title="Lihat Riwayat Aktivitas">
                                                <i class="bi bi-clock-history"></i>
                                            </button>


                                            <button v-if="hasPermission('lead-edit') && item.status !== 'CLOSED_WON'"
                                                @click="openEditModal(item)"
                                                :class="`btn btn-c-success ${item.status === 'CLOSED_WON' ? 'disabled' : ''}`"
                                                title="Edit Data">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>

                                            <template v-if="item.status !== 'CLOSED_WON'">
                                                <button @click="openConvertModal(item)" class="btn btn-c-info"
                                                    title="Sahkan Closing">
                                                    <i class="bi bi-rocket-takeoff-fill"></i>
                                                </button>
                                            </template>


                                            <template v-else>
                                                <Link :href="route('sales.index', { keyword: item.invoice })"
                                                    class="btn btn-c-primary" title="Lihat Invoice">
                                                    <i class="bi bi-receipt"></i>
                                                </Link>
                                            </template>

                                            <button v-if="hasPermission('lead-delete') && item.status !== 'CLOSED_WON'"
                                                @click="deleted('leads.delete', item)" class="btn btn-c-primary"
                                                title="Hapus Lead">
                                                <i class="bi bi-trash"></i>
                                            </button>

                                        </div>
                                    </td>

                                </template>


                                <template #pagination>
                                    <div
                                        class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center ">
                                        <small class="text-muted mb-2 mb-md-0">
                                            Menampilkan <strong>{{ leads.from ?? 0 }}</strong> - <strong>{{
                                                leads.to ?? 0 }}</strong> dari <strong>{{ leads.total
                                                    ?? 0
                                                }}</strong>
                                        </small>
                                        <pagination size="pagination-sm" :links="leads?.links" routeName="leads.index"
                                            :additionalQuery="{
                                                keyword: filters.keyword,
                                                status: filters.status,
                                                periode: filters.periode,
                                                limit: filters.limit,
                                                order_by: filters.order_by,
                                            }" :only="['leads', 'filters', 'channels', 'products']" />
                                    </div>
                                </template>


                            </table-wrapper>
                        </div>
                    </div>
                </div>
                <FormModal @saved="handleLeadSaved" :channels="props.channels" :products="props.products"
                    :idLead="selectedId" :show="showModal" @update:show="showModal = $event" />
                <ConvertLeadModal :idLead="selectedId" :show="showConvertModal"
                    @update:show="showConvertModal = $event" />

                <ActivityTimelineModal :idLead="selectedId" :show="showHistoryModal"
                    @update:show="showHistoryModal = $event" />
            </div>
        </template>
    </app-layout>
</template>
