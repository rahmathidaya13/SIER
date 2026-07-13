<script setup>
import { Head } from '@inertiajs/vue3';
import { useFilter } from '@/Composables/useFilter';
import { hasRole } from '@/Composables/useAuth';
import { computed, ref } from 'vue';
const props = defineProps({
    stats: Object,
    branches: {
        type: Array,
        default: () => [],
    },
    filters: Object
})
const {
    filters,
} = useFilter({
    routeName: "home",
    initialFilters: {
        month: props.filters.month || new Date().getMonth() + 1,
        year: props.filters.year || new Date().getFullYear(),
        branch: props.filters.branch || null,
    },
    watchFields: [
        'month',
        'year',
        'branch',
    ],
    only: ['stats', 'filters', 'branches'],
    debounceTime: 500,
});
// Fungsi untuk menyingkat angka besar (Misal: 48500000 -> 48.5M)
const formatShortCurrency = (value) => {
    if (!value) return 'Rp 0';
    if (value >= 1000000000) {
        return 'Rp ' + (value / 1000000000).toFixed(1) + 'M'; // Miliar
    }
    if (value >= 1000000) {
        return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt'; // Juta
    }
    // Jika di bawah 1 Juta, tampilkan normal
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
};

// ... (helper format mata uang yang sebelumnya biarkan saja)

const funnelLabels = computed(() => {
    return props.stats?.monthly_trend?.map(item => item.label) || [];
});

// 2. Dataset Multiple Bar (Prospek vs Closing)
const funnelChartData = computed(() => {
    const trend = props.stats?.monthly_trend || [];
    const countNumber = props.stats?.monthly_trend.map(val => Number(val) || 0);

    const colorPalette = [
        'rgba(59, 130, 246, 0.8)',   // Biru
        'rgba(16, 185, 129, 0.8)',   // Hijau
        'rgba(245, 158, 11, 0.8)',   // Oranye
        'rgba(239, 68, 68, 0.8)',    // Merah
        'rgba(139, 92, 246, 0.8)',   // Ungu
        'rgba(236, 72, 153, 0.8)',   // Pink
        'rgba(20, 184, 166, 0.8)',   // Teal
    ];

    const hoverPalette = [
        'rgba(37, 99, 235, 1)',
        'rgba(5, 150, 105, 1)',
        'rgba(217, 119, 6, 1)',
        'rgba(220, 38, 38, 1)',
        'rgba(124, 58, 237, 1)',
        'rgba(219, 39, 119, 1)',
        'rgba(13, 148, 136, 1)',
    ];

    const backgroundColors = countNumber.map((_, index) =>
        colorPalette[index % colorPalette.length]
    );

    const hoverColors = countNumber.map((_, index) =>
        hoverPalette[index % hoverPalette.length]
    );
    return [
        {
            label: 'Prospek Masuk',
            data: trend.map(item => item.total),
            backgroundColor: backgroundColors,
            hoverBackgroundColor: hoverColors,
            borderRadius: 4,
            barPercentage: 0.6,
        },
        {
            label: 'Berhasil Closing',
            data: trend.map(item => item.closed),
            backgroundColor: backgroundColors,
            hoverBackgroundColor: hoverColors,
            borderRadius: 4,
            barPercentage: 0.6,
        }
    ];
});

// List bulan untuk dropdown
const months = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' }
];
// List tahun (5 tahun terakhir)
const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

const branchOptions = computed(() => [
    {
        label: 'Semua Cabang',
        value: 'all',
    },
    ...props.branches.map(data => ({
        label: data.name,
        value: data.name,
    }))
])
console.log((Math.max(props.stats.total_leads, 1)));
</script>
<template>

    <Head title="Home" />
    <app-layout>
        <template #content>
            <div class="row g-3 view-section">
                <callout />

                <div class="card p-2 d-flex border overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="d-grid px-3">
                            <h5 class="fw-bold mb-1">
                                <i class="bi bi-funnel-fill"></i>
                                Saring Prospek
                            </h5>
                            <small class="px-4 text-muted">Filter berdasarkan Bulan dan Tahun</small>
                        </span>
                        <div class="d-flex gap-2 bg-white p-2 rounded-3 border text-dark align-items-center ms-auto">

                            <select-input v-if="branches.length > 0 && hasRole('super_admin')" :isValid="false"
                                text="Pilih Cabang" name="branch" :options="branchOptions" v-model="filters.branch" />
                            <div v-if="branches.length > 0 && hasRole('super_admin')" class="vr my-1"></div>
                            <select name="month" id="month" v-model="filters.month"
                                class="form-select form-select-sm focus-none fw-semibold">
                                <option v-for="month in months" :key="month.value" :value="month.value">{{
                                    month.label
                                    }}
                                </option>
                            </select>
                            <div class="vr my-1"></div>
                            <select name="year" id="year" v-model="filters.year"
                                class="form-select form-select-sm focus-none fw-semibold">
                                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                            </select>
                        </div>
                    </div>
                </div>


                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-medium">Total Prospek (Leads)</p>
                                <h3 class="fw-bold mb-0 text-info">{{ stats.total_leads }}</h3>
                            </div>
                            <div class="stats-icon bg-info bg-opacity-10 text-info"><i class="bi bi-people"></i>
                            </div>
                        </div>
                        <small class="mt-3 d-block" :class="stats.growth_leads >= 0 ? 'text-success' : 'text-danger'">
                            <i class="bi"
                                :class="stats.growth_leads >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short'"></i>
                            {{ stats.growth_leads >= 0 ? '+' : '' }}{{ stats.growth_leads }} Lead dibanding bulan lalu
                        </small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-medium">Berhasil Closing</p>
                                <h3 class="fw-bold mb-0 text-success">{{ stats.closed_leads }}</h3>
                            </div>
                            <div class="stats-icon bg-success bg-opacity-10 text-success"><i
                                    class="bi bi-check2-circle"></i></div>
                        </div>
                        <small class="mt-3 d-block"
                            :class="stats.growth_closed_percent >= 0 ? 'text-success' : 'text-danger'">
                            <i class="bi"
                                :class="stats.growth_closed_percent >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short'"></i>
                            {{ stats.growth_closed_percent >= 0 ? 'Naik' : 'Turun' }} {{
                                Math.abs(stats.growth_closed_percent) }}%
                        </small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100 border border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-medium">Rata-rata Closing</p>
                                <h3 class="fw-bold mb-0 text-primary">{{ stats.closing_rate }}%</h3>
                            </div>
                            <div class="stats-icon bg-primary bg-opacity-10 text-primary"><i
                                    class="bi bi-lightning-charge"></i></div>
                        </div>
                        <small class="mt-3 d-block" :class="stats.closing_rate >= 20 ? 'text-success' : 'text-danger'">
                            {{ stats.closing_rate >= 20 ? 'Performa Sehat' : 'Perlu Evaluasi' }}
                        </small>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-medium">Nominal Closing</p>
                                <h3 class="fw-bold mb-0 fs-4">{{ formatShortCurrency(stats.total_nominal) }}</h3>
                            </div>
                            <div class="stats-icon bg-warning bg-opacity-10 text-warning"><i
                                    class="bi bi-cash-stack"></i></div>
                        </div>
                        <small class="text-success mt-3 d-block">
                            {{ stats.lunas_rate }}% Lunas Terbayar
                        </small>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                        <h6 class="fw-bold mb-4">Sebaran Status Prospek</h6>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Prospek Baru (New)</span>
                                <span class="fw-bold">{{ stats.status?.new || 0 }}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info"
                                    :style="{ width: (stats.status?.new / Math.max(stats.total_leads, 1) * 100) + '%' }">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Sedang Follow Up</span>
                                <span class="fw-bold">{{ stats.status?.follow_up || 0 }}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning"
                                    :style="{ width: (stats.status?.follow_up / Math.max(stats.total_leads, 1) * 100) + '%' }">
                                </div>
                            </div>
                        </div>

                        <div class="mb-0">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Gagal (Lost)</span>
                                <span class="fw-bold">{{ stats.status?.closed_lost || 0 }}</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-danger"
                                    :style="{ width: (stats.status?.closed_lost / Math.max(stats.total_leads, 1) * 100) + '%' }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                        <h6 class="fw-bold mb-4">Kualitas Leads (Temperature)</h6>

                        <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-danger bg-opacity-10 rounded-3">
                            <div class="fs-4 text-danger"><i class="bi bi-fire"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-bold text-danger">HOT (Siap Beli)</span>
                                    <span class="small fw-bold text-danger">{{ stats.temperature?.hot || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-warning bg-opacity-10 rounded-3">
                            <div class="fs-4 text-warning"><i class="bi bi-sun"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-bold text-warning">WARM (Pikir-pikir)</span>
                                    <span class="small fw-bold text-warning">{{ stats.temperature?.warm || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 p-2 bg-info bg-opacity-10 rounded-3">
                            <div class="fs-4 text-info"><i class="bi bi-snow"></i></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-bold text-info">COLD (Hanya Tanya)</span>
                                    <span class="small fw-bold text-info">{{ stats.temperature?.cold || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div
                        class="card border-0 shadow-sm rounded-4 p-4 h-100 text-center d-flex flex-column justify-content-center">
                        <h6 class="fw-bold mb-4 text-start">Status Pembayaran</h6>

                        <div class="d-flex justify-content-around">
                            <div>
                                <h2 class="fw-bold text-success mb-0">{{ stats.payment?.lunas || 0 }}</h2>
                                <p class="text-muted small">Transaksi Lunas</p>
                            </div>
                            <div class="border-end"></div>
                            <div>
                                <h2 class="fw-bold text-primary mb-0">{{ stats.payment?.dp || 0 }}</h2>
                                <p class="text-muted small">Pembayaran DP</p>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded-3 text-start small text-muted border-0">
                            💡 Data ini merepresentasikan tagihan transaksi yang masuk ke dalam sistem pada bulan
                            berjalan.
                        </div>
                    </div>
                </div>


                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <h6 class="fw-bold mb-1">Statistik Konversi Funnel</h6>
                                <p class="text-muted small">Tren prospek masuk vs closing dalam 7 hari terakhir</p>
                            </div>
                            <div class="badge bg-light text-dark border">Harian</div>
                        </div>

                        <div class="py-2 mt-auto" style="height: 400px;">
                            <chart-bar type="pie" :height="400" :labels="funnelLabels" :datasets="funnelChartData"
                                tooltip-suffix=" Leads" />
                        </div>
                    </div>
                </div>
            </div>


        </template>
    </app-layout>
</template>
