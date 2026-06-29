<script setup>
import { Form, Head, usePage, router, Link } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import { useFilter } from '@/Composables/useFilter';
import { useConfirm } from "@/Composables/useConfirm"
import { highlight } from "@/Helpers/Highlight";
import FormModal from './FormModal.vue';
import axios from 'axios';
const props = defineProps({
    leads: Object,
    filters: Object,
    channels: Array,
    products: Array,
    role_name: String
});
console.log(props.leads.data);
const {
    filters,
    isLoading,
} = useFilter({
    routeName: "leads.index",
    initialFilters: {
        keyword: props.filters.keyword ?? '',
        limit: props.filters.limit ?? 10,
        status: props.filters.status ?? 'all',
        temperature: props.filters.temperature ?? 'all',
        order_by: props.filters.order_by ?? 'latest',
        page: props.filters.page ?? 1,
    },
    watchFields: [
        'keyword',
        'limit',
        'status',
        'temperature',
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
        key: "status",
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
const openEditModal = async (data) => {
    showModal.value = true;
    selectedId.value = data.id_lead
}
const keywordRef = ref(null);
onMounted(() => {
    keywordRef.value.focus();
})

const leadActions = [
    {
        label: 'Ubah Data Lead',
        action: 'edit',
        protectedSelf: true,
        icon: 'bi bi-pencil-square',
        title: 'Ubah Data Lead',
        buttonClass: 'btn-action',
        variant: 'edit',
    },
    {
        label: 'Hapus Data Lead',
        action: 'delete',
        protectedSelf: true,
        icon: 'bi bi-trash',
        buttonClass: 'btn-action',
        variant: 'delete',
        title: 'Hapus Data Lead',
    },
];
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
        'HOT': { class: 'badge-soft-success', label: '🔥 HOT' },
        'WARM': { class: 'badge-soft-warning', label: '⏳ WARM' },
        'COLD': { class: 'badge-soft-danger', label: '❄️ COLD' }
    };
    return map[temp] || { class: 'badge-soft-dark', label: temp };
};
const getStatusBadge = (status) => {
    const map = {
        'NEW': { class: 'badge-soft-primary', label: '⭐ Baru Masuk' },
        'FOLLOW_UP': { class: 'badge-soft-info', label: '💬 Follow Up' },
        'CLOSED_WON': { class: 'badge-soft-success fw-bold', label: '✔ CLOSING (Terjual / Deal)' },
        'CLOSED_LOST': { class: 'badge-soft-dark', label: '✖ Batal / Lost' }
    };
    return map[status] || { class: 'badge-soft-dark', label: status };
};

</script>
<template>

    <Head title="Data Rekap Leads" />
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
                                    <h5 class="fw-bold mb-1">Database Prospek (Leads)</h5>
                                    <small class="text-muted">Prospek yang belum melakukan pembayaran deal</small>
                                </div>
                                <button @click.prevent="openCreateModal" class="btn btn-c-primary px-4 py-2">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Lead Baru
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
                                <select-input :isValid="false" name="temperature" v-model="filters.temperature"
                                    text="Suhu Prospek" :options="[
                                        { label: '🌍 Semua Suhu', value: 'all' },
                                        { label: '🔥 HOT (Siap Bayar)', value: 'hot' },
                                        { label: '🌤️ WARM (Tanya Detail)', value: 'warm' },
                                        { label: '❄️ COLD (Hanya Kepo)', value: 'cold' }
                                    ]" />
                            </div>
                            <div class="col-xl-2 col-3 col-md-3 col-lg-3">
                                <select-input :isValid="false" name="status" v-model="filters.status"
                                    text="Status Prospek" :options="[
                                        { label: '📋 Semua Status', value: 'all' },
                                        { label: '🆕 Baru Masuk', value: 'new' },
                                        { label: '📞 Follow Up', value: 'follow_up' },
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
                                :items="leads?.data" row-key="product_id"
                                @update:selected="(val) => selectedData = val">

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
                                            <a :href="getEmail(item.email)" class="text-decoration-none"
                                                target="_blank">{{ item.email
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
                                            :class="getTempBadge(item.temperature).class">
                                            {{ getTempBadge(item.temperature).label }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge rounded-pill px-3 py-1"
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
                                            {{ new Date(item.follow_up_date).toLocaleDateString('id-ID', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric'
                                            }) }}
                                        </div>
                                        <span v-else class="text-muted small">-</span>
                                    </td>

                                    <td class="text-center">
                                        <action-button :item="item" :actions="leadActions" @edit="openEditModal(item)"
                                            @delete="deleted('leads.delete', item)" />
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
                                                limit: filters.limit,
                                                order_by: filters.order_by,
                                                temperature: filters.temperature
                                            }" :only="['leads', 'filters', 'channels', 'products']" />
                                    </div>
                                </template>


                            </table-wrapper>
                        </div>
                    </div>
                </div>
                <FormModal :channels="props.channels" :products="props.products" :idLead="selectedId" :show="showModal"
                    @update:show="showModal = $event" />
            </div>
        </template>
    </app-layout>
</template>
