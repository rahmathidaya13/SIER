<script setup>
import { Form, Head, usePage, router, Link } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import { useFilter } from '@/Composables/useFilter';
import { useConfirm } from "@/Composables/useConfirm"
import { highlight } from "@/Helpers/Highlight";
import { formatDate } from '@/Helpers/formatDate';
import { currencyFormat } from '@/Helpers/currencyFormat';
import { hasRole } from '@/Composables/useAuth';
import FormModal from './FormModal.vue';
const confirm = useConfirm();

const props = defineProps({
    channels: Object,
    filters: Object,
});
const showModal = ref(false);
const selectedData = ref(null);

const openCreateModal = () => {
    showModal.value = true;
    selectedData.value = null;
}
const openEditModal = async (data) => {
    showModal.value = true;
    selectedData.value = data;
}
const {
    filters,
    isLoading,
} = useFilter({
    routeName: "channel.index",
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
    only: ['channels', 'filters'],
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
        label: "SUMBER / PLATFORM",
        key: "platform",
        attrs: {
            class: 'text-center',
        },
    },
    {
        label: "LEAD MASUK",
        key: "total_leads",
        attrs: {
            class: 'text-center',
        },
    },
    {
        label: "CLOSING (DEAL)",
        key: "total_deals",
        attrs: {
            class: 'text-center',
        },
    },
    {
        label: "RASIO KONVERSI (%)",
        key: "conversion_rate",
        attrs: {
            style: {
                width: '200px'
            },
            class: 'text-center',
        },
    },
    {
        label: "Aksi",
        key: "actions",
        visible: hasRole('super_admin'),
        attrs: {
            class: 'text-center'
        },
    },
];

const getProgressColor = (rate) => {
    if (rate >= 50) return 'bg-c-success'; // Sangat Bagus (>50%)
    if (rate >= 20) return 'bg-c-info'; // Normal (20% - 49%)
    if (rate > 0) return 'bg-c-warning';   // Perlu Evaluasi (1% - 19%)
    return 'bg-c-danger';                  // Mati / 0%
};


// Sensor Cerdas untuk Kesimpulan Otomatis
const bestChannel = computed(() => {
    if (!props.channels.data || props.channels.data.length === 0) return null;
    return [...props.channels.data].sort((a, b) => b.conversion_rate - a.conversion_rate)[0];
});
// Palette warna RGBA (Opacity 0.85 untuk warna dasar)
const chartColors = [
    'rgba(25, 135, 84, 0.85)',   // Hijau (Success)
    'rgba(220, 53, 69, 0.85)',   // Merah (Danger)
    'rgba(13, 110, 253, 0.85)',  // Biru (Primary)
    'rgba(255, 193, 7, 0.85)',   // Kuning (Warning)
    'rgba(111, 66, 193, 0.85)',  // Ungu (Purple)
    'rgba(13, 202, 240, 0.85)',  // Cyan (Info)
    'rgba(33, 37, 41, 0.85)'     // Gelap (Dark)
];

// Palette warna RGBA (Opacity 1 untuk efek Hover)
const chartHoverColors = [
    'rgba(25, 135, 84, 1)',
    'rgba(220, 53, 69, 1)',
    'rgba(13, 110, 253, 1)',
    'rgba(255, 193, 7, 1)',
    'rgba(111, 66, 193, 1)',
    'rgba(13, 202, 240, 1)',
    'rgba(33, 37, 41, 1)'
];

// 1. Labels
const channelLabels = computed(() => {
    return props.channels.data.map(item => item.name);
});

// 2. Datasets
const channelChartData = computed(() => {
    const dataValues = props.channels.data.map(item => item.total_leads);
    const backgroundColors = props.channels.data.map((_, index) => chartColors[index % chartColors.length]);
    const hoverColors = props.channels.data.map((_, index) => chartHoverColors[index % chartHoverColors.length]);
    return [
        {
            label: 'Total Leads Masuk',
            data: dataValues,
            backgroundColor: backgroundColors,
            hoverBackgroundColor: hoverColors, // Warna hover disamakan atau bisa sedikit digelapkan
            borderWidth: 4,
            borderColor: '#ffffff',   // Memberikan garis putih pembatas antar potongan donat
            hoverOffset: 12
        }
    ];
});

</script>
<template>

    <Head title="Performa Trafik Channel" />
    <app-layout>
        <template #content>
            <div class="row g-3 view-section">
                <callout />
                <div class="col-12 col-md-12 col-lg-4 col-xl-4">
                    <div class="card border-0 rounded-4 overflow-hidden">

                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Pangsa Volume Trafik</h5>
                                    <small class="text-muted">Persentase distribusi sebaran prospek masuk</small>
                                </div>
                            </div>
                        </div>

                        <div class="py-3">
                            <chart-bar type="doughnut" :height="280" :labels="channelLabels"
                                :datasets="channelChartData" :hide-scales="true" tooltip-suffix=" Leads" />
                        </div>

                        <div class="p-3 bg-light rounded-3 text-start small text-muted border-0">
                            💡 <b>Kesimpulan Otomatis:</b>
                            <span v-if="bestChannel && bestChannel.total_leads > 0">
                                Performa terbaik saat ini dipegang oleh channel <b>{{ bestChannel.name }}</b> dengan
                                rasio closing
                                sebesar <b>{{ bestChannel.conversion_rate }}%</b>. Pertahankan stabilitas alokasi di
                                channel ini.
                            </span>
                            <span v-else>
                                Belum ada aktivitas konversi data transaksi untuk ditarik kesimpulan.
                            </span>
                        </div>

                    </div>
                </div>
                 <div class="col-12 col-md-12 col-lg-8 col-xl-8">
                    <div class="card border-0 rounded-4 overflow-hidden">

                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Performa Akusisi per Channel</h5>
                                    <small class="text-muted">Mengetahui dari mana prospek paling berkualitas
                                        datang</small>
                                </div>
                                <button v-if="hasRole('super_admin')" @click.prevent="openCreateModal"
                                    class="btn btn-c-primary px-4 py-2">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Channel
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0">
                            <table-wrapper :loader="isLoading" loaderText="Sedang memuat data..." :headers="header"
                                :items="channels?.data" rowKey="id_channel"
                                @update:selected="(val) => selectedData = val">

                                <template #empty>
                                    <div class="text-center py-5">
                                        <i class="bi bi-funnel fa-3x text-muted opacity-25 mb-3 d-block"></i>
                                        <h6 class="text-muted fw-bold">Data channels Anda Masih Kosong</h6>
                                        <p class="text-muted small mb-0">Klik tombol "Tambah Channel" di pojok kanan
                                            atas untuk membuat channel baru</p>
                                    </div>
                                </template>

                                <template #row="{ item, index }">
                                    <td class="td-number">
                                        {{ index + 1 + (channels?.current_page - 1) * channels?.per_page }}
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                                                style="width: 40px; height: 40px;">
                                                <i class="fs-5" :class="item.icon || 'bi-box'"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ item.name }}</div>
                                                <div class="text-muted small">Kode: {{ item.code }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-c-light text-dark border px-3 py-2 shadow-sm">
                                            {{ item.total_leads }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-c-success px-3 py-2 shadow-sm">
                                            <i class="bi bi-check-circle-fill me-1"></i> {{ item.closed_leads }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar rounded-pill" role="progressbar"
                                                    :class="getProgressColor(item.conversion_rate)"
                                                    :style="{ width: item.conversion_rate + '%' }"
                                                    :aria-valuenow="item.conversion_rate" aria-valuemin="0"
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                            <div class="fw-bold text-dark" style="width: 45px;">{{
                                                item.conversion_rate }}%</div>
                                        </div>
                                    </td>

                                    <td v-if="hasRole('super_admin')" class="text-center pe-4 py-3">
                                        <button @click.prevent="openEditModal(item.id_channel)" type="button"
                                            class="btn btn-c-slate" title="Edit Channel">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                    </td>

                                </template>


                                <template #pagination>
                                    <div
                                        class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center ">
                                        <small class="text-muted mb-2 mb-md-0">
                                            Menampilkan <strong>{{ channels.from ?? 0 }}</strong> - <strong>{{
                                                channels.to ?? 0 }}</strong> dari <strong>{{ channels.total
                                                    ?? 0
                                                }}</strong>
                                        </small>
                                        <pagination size="pagination-sm" :links="channels?.links"
                                            routeName="channel.index" :additionalQuery="{
                                                keyword: filters.keyword,
                                                limit: filters.limit,
                                                order_by: filters.order_by,
                                            }" :only="['channels', 'filters']" />
                                    </div>
                                </template>


                            </table-wrapper>
                        </div>
                    </div>
                </div>
            </div>
            <FormModal :idChannel="selectedData" :show="showModal" @update:show="showModal = $event" />
        </template>
    </app-layout>
</template>
