<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { useFilter } from "@/Composables/useFilter";
import { hasRole } from "@/Composables/useAuth";
import { computed, ref } from "vue";
const props = defineProps({
  stats: Object,
  branches: {
    type: Array,
    default: () => [],
  },
  filters: Object,
});
console.log(props.stats);
const page = usePage();
const { isLoading, filters } = useFilter({
  routeName: "home",
  initialFilters: {
    month: props.filters?.month || new Date().getMonth() + 1,
    year: props.filters?.year || new Date().getFullYear(),
    branch: props.filters?.branch || "",
  },
  watchFields: ["month", "year", "branch"],
  only: ["stats", "filters", "branches"],
  debounceTime: 400,
});

// Format angka ringkas untuk tampilan utama kartu (Misal: 48500000 -> Rp 48,5 Jt)
const formatShortCurrency = (value) => {
  const num = Number(value) || 0;
  if (num === 0) return "Rp 0";

  if (num >= 1000000000) {
    return "Rp " + (num / 1000000000).toFixed(1).replace(".", ",") + " M"; // Miliar
  }

  if (num >= 1000000) {
    return "Rp " + (num / 1000000).toFixed(1).replace(".", ",") + " Jt"; // Juta
  }
  // Jika di bawah 1 Juta, tampilkan normal
  return "Rp " + new Intl.NumberFormat("id-ID").format(num);
};

// Format angka penuh untuk tooltip / rincian detail
const formatFullCurrency = (value) => {
  return "Rp " + new Intl.NumberFormat("id-ID").format(Number(value) || 0);
};

// Label konteks cakupan data sesuai role user
const scopeContextLabel = computed(() => {
  if (hasRole(["developer", "super_admin"])) {
    return filters.branch && filters.branch !== "all"
      ? `Cabang ${filters.branch}`
      : "Semua Cabang (Nasional)";
  }
  if (hasRole("supervisor")) {
    const branchName = page.props.auth?.user?.branch?.name;
    return branchName ? `Cabang ${branchName}` : "Performa Cabang Anda";
  }
  return "Performa Pribadi Anda";
});

const funnelLabels = computed(() => {
  return props.stats?.monthly_trend?.map((item) => item.label) || [];
});

// 2. Dataset Multiple Bar (Prospek vs Closing)
const funnelChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "top",
    },
    tooltip: {
      callbacks: {
        afterBody: (context) => {
          if (!context.length) return "";
          const index = context[0].dataIndex;
          const item = props.stats?.monthly_trend?.[index];
          if (!item) return "";

          const total = Number(item.total) || 0;
          const closed = Number(item.closed) || 0;

          const conversion = total > 0 ? ((closed / total) * 100).toFixed(1) : 0;
          return `Rasio Konversi: ${conversion}%`;
        },
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0,
      },
    },
  },
};
const funnelChartData = computed(() => {
  const trend = props.stats?.monthly_trend || [];

  return [
    {
      label: "Prospek Masuk",
      data: trend.map((item) => Number(item.total) || 0),
      backgroundColor: "rgba(59, 130, 246, 0.75)",
      hoverBackgroundColor: "rgba(37, 99, 235, 1)",
      borderColor: "rgba(59, 130, 246, 1)",
      borderRadius: 4,
      barPercentage: 0.9,
      categoryPercentage: 1,
    },

    {
      label: "Berhasil Closing",
      data: trend.map((item) => Number(item.closed) || 0),
      backgroundColor: "rgba(16, 185, 129, 0.75)",
      hoverBackgroundColor: "rgba(5, 150, 105, 1)",
      borderColor: "rgba(16, 185, 129, 1)",
      borderRadius: 4,
      barPercentage: 0.85,
      categoryPercentage: 0.8,
    },
  ];
});
// List bulan untuk dropdown
const months = [
  { value: 1, label: "Januari" },
  { value: 2, label: "Februari" },
  { value: 3, label: "Maret" },
  { value: 4, label: "April" },
  { value: 5, label: "Mei" },
  { value: 6, label: "Juni" },
  { value: 7, label: "Juli" },
  { value: 8, label: "Agustus" },
  { value: 9, label: "September" },
  { value: 10, label: "Oktober" },
  { value: 11, label: "November" },
  { value: 12, label: "Desember" },
];
// List tahun (5 tahun terakhir)
const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);
const selectedMonthName = computed(() => {
  return months.find((m) => m.value === Number(filters.month))?.label || "";
});
const branchOptions = computed(() =>
  props.branches.map((data) => ({
    label: data.status === "official" ? `${data.name} (Official)` : data.name,
    value: data.name,
  }))
);
</script>
<template>
  <Head title="Dashboard" />
  <app-layout>
    <template #content>
      <div class="row g-3 view-section">
        <toast :duration="10" />

        <div class="col-12">
          <div class="card p-3 border rounded-4 shadow-sm">
            <div
              class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
            >
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <h5 class="fw-bold mb-0">
                    <i class="bi bi-speedometer2 me-1 text-primary"></i>
                    Ringkasan Performa
                  </h5>
                  <span
                    class="badge bg-primary-subtle text-primary border border-primary-subtle"
                  >
                    {{ scopeContextLabel }}
                  </span>
                  <span
                    v-if="isLoading"
                    class="spinner-border spinner-border-sm text-primary"
                    role="status"
                  ></span>
                </div>
                <small class="text-muted">
                  Menampilkan statistik periode
                  <strong>{{ selectedMonthName }} {{ filters.year }}</strong>
                </small>
              </div>

              <div
                class="d-flex flex-wrap gap-2 bg-light p-2 rounded-3 border text-dark align-items-center"
              >
                <template
                  v-if="branches.length > 0 && hasRole(['super_admin', 'developer'])"
                >
                  <select-input
                    :isValid="false"
                    text="Pilih Cabang"
                    name="branch"
                    :options="branchOptions"
                    v-model="filters.branch"
                    selectClass="fw-semibold w-auto"
                  />

                  <div class="vr my-1 d-none d-sm-block"></div>
                </template>

                <select-input
                  :isValid="false"
                  text="Pilih Bulan"
                  name="month"
                  v-model="filters.month"
                  :options="months"
                  selectClass="fw-semibold w-auto"
                />

                <div class="vr my-1"></div>

                <select
                  name="year"
                  id="year"
                  v-model="filters.year"
                  class="form-select form-select-sm focus-none fw-semibold w-auto"
                >
                  <option v-for="year in years" :key="year" :value="year">
                    {{ year }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- KPI 1: Total Prospek -->
        <div class="col-sm-6 col-xl-3">
          <div class="card p-3 h-100 rounded-4 custom-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small fw-medium">Total Prospek (Leads)</p>
                <h3 class="fw-bold mb-0 text-info">{{ stats.total_leads }}</h3>
              </div>
              <div class="stats-icon bg-info bg-opacity-10 text-info">
                <i class="bi bi-people"></i>
              </div>
            </div>
            <small
              class="mt-3 d-block"
              :class="stats.growth_leads >= 0 ? 'text-success' : 'text-danger'"
            >
              <i
                class="bi"
                :class="
                  stats.growth_leads >= 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short'
                "
              ></i>
              {{ stats.growth_leads >= 0 ? "+" : "" }}{{ stats.growth_leads }} Lead
              dibanding bulan lalu
            </small>
          </div>
        </div>

        <!-- KPI 2: Berhasil Closing -->
        <div class="col-sm-6 col-xl-3">
          <div class="card p-3 h-100 rounded-4 custom-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small fw-medium">Berhasil Closing</p>
                <h3 class="fw-bold mb-0 text-success">{{ stats.closed_leads }}</h3>
              </div>
              <div class="stats-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-check2-circle"></i>
              </div>
            </div>
            <small
              class="mt-3 d-block"
              :class="stats.growth_closed_percent >= 0 ? 'text-success' : 'text-danger'"
            >
              <i
                class="bi"
                :class="
                  stats.growth_closed_percent >= 0
                    ? 'bi-arrow-up-short'
                    : 'bi-arrow-down-short'
                "
              ></i>
              {{ stats.growth_closed_percent >= 0 ? "Naik" : "Turun" }}
              {{ Math.abs(stats.growth_closed_percent) }}% dari bulan lalu
            </small>
          </div>
        </div>

        <!-- KPI 3: Rasio Closing -->
        <div class="col-sm-6 col-xl-3">
          <div class="card p-3 h-100 rounded-4 custom-card">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small fw-medium">Rasio Closing</p>
                <h3 class="fw-bold mb-0 text-primary">{{ stats.closing_rate }}%</h3>
              </div>
              <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-lightning-charge"></i>
              </div>
            </div>
            <small
              class="mt-3 d-block"
              :class="stats.closing_rate >= 20 ? 'text-success' : 'text-warning'"
            >
              <i
                class="bi me-1"
                :class="
                  stats.closing_rate >= 20 ? 'bi-check-circle' : 'bi-exclamation-circle'
                "
              ></i>
              {{
                stats.closing_rate >= 20
                  ? "Konversi Sehat (≥ 20%)"
                  : "Perlu Evaluasi (< 20%)"
              }}
            </small>
          </div>
        </div>

        <!-- KPI 4: Omset Masuk & Total Deal -->
        <div class="col-sm-6 col-xl-3">
          <div
            class="card p-3 h-100 rounded-4 custom-card"
            :title="`Total Deal: ${formatFullCurrency(
              stats.total_deal
            )} | Terbayar: ${formatFullCurrency(stats.total_nominal)}`"
          >
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="text-muted mb-1 small fw-medium">Omset Masuk (Terbayar)</p>
                <h3 class="fw-bold mb-0 fs-4 text-dark">
                  {{ formatShortCurrency(stats.total_nominal) }}
                </h3>
              </div>
              <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-cash-stack"></i>
              </div>
            </div>
            <small class="text-muted mt-3 d-block">
              Dari total deal
              <strong class="text-dark">{{
                formatShortCurrency(stats.total_deal)
              }}</strong>
              (<span class="text-success fw-semibold">{{ stats.lunas_rate }}%</span>)
            </small>
          </div>
        </div>

        <!-- Grafik Konversi Funnel -->
        <div class="col-12">
          <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div>
                <h6 class="fw-bold mb-1">Statistik Konversi Funnel Harian</h6>
                <p class="text-muted small mb-0">
                  Perbandingan prospek masuk vs closing selama bulan
                  {{ selectedMonthName }} {{ filters.year }}
                </p>
              </div>
              <span class="badge bg-light text-dark border">Harian</span>
            </div>

            <div class="py-2 mt-2" style="height: 380px">
              <chart-bar
                type="bar"
                :height="380"
                :labels="funnelLabels"
                :datasets="funnelChartData"
                tooltip-suffix=" Leads"
                :options="funnelChartOptions"
              />
            </div>
          </div>
        </div>

        <!-- Rincian 1: Sebaran Status Prospek (Lengkap 4 Status) -->
        <div class="col-lg-4">
          <div class="card rounded-4 p-4 h-100 custom-card">
            <h6 class="fw-bold mb-4">Sebaran Status Prospek</h6>

            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Prospek Baru (New)</span>
                <span class="fw-bold">{{ stats.status?.new || 0 }}</span>
              </div>
              <div class="progress" style="height: 8px">
                <div
                  class="progress-bar bg-info"
                  :style="{
                    width:
                      ((stats.status?.new || 0) / Math.max(stats.total_leads, 1)) * 100 +
                      '%',
                  }"
                ></div>
              </div>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Sedang Follow Up</span>
                <span class="fw-bold">{{ stats.status?.follow_up || 0 }}</span>
              </div>
              <div class="progress" style="height: 8px">
                <div
                  class="progress-bar bg-warning"
                  :style="{
                    width:
                      ((stats.status?.follow_up || 0) / Math.max(stats.total_leads, 1)) *
                        100 +
                      '%',
                  }"
                ></div>
              </div>
            </div>

            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Berhasil Closing (Won)</span>
                <span class="fw-bold text-success">{{
                  stats.status?.closed_won || 0
                }}</span>
              </div>
              <div class="progress" style="height: 8px">
                <div
                  class="progress-bar bg-success"
                  :style="{
                    width:
                      ((stats.status?.closed_won || 0) / Math.max(stats.total_leads, 1)) *
                        100 +
                      '%',
                  }"
                ></div>
              </div>
            </div>

            <div class="mb-0">
              <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Gagal (Lost)</span>
                <span class="fw-bold text-danger">{{
                  stats.status?.closed_lost || 0
                }}</span>
              </div>
              <div class="progress" style="height: 8px">
                <div
                  class="progress-bar bg-danger"
                  :style="{
                    width:
                      ((stats.status?.closed_lost || 0) /
                        Math.max(stats.total_leads, 1)) *
                        100 +
                      '%',
                  }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rincian 2: Kualitas Leads (Temperature) -->
        <div class="col-lg-4">
          <div class="card rounded-4 p-4 h-100 custom-card">
            <h6 class="fw-bold mb-4">Kualitas Leads (Temperature)</h6>

            <div
              class="d-flex border border-danger align-items-center gap-3 mb-3 p-2 px-3 bg-danger bg-opacity-10 rounded-3"
            >
              <div class="fs-4 text-danger"><i class="bi bi-fire"></i></div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="small fw-bold text-danger">HOT (Siap Closing)</span>
                  <span class="badge bg-danger">{{ stats.temperature?.hot || 0 }}</span>
                </div>
              </div>
            </div>

            <div
              class="d-flex border border-warning align-items-center gap-3 mb-3 p-2 px-3 bg-warning bg-opacity-10 rounded-3"
            >
              <div class="fs-4 text-warning"><i class="bi bi-sun"></i></div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="small fw-bold text-dark">WARM (Mempertimbangkan)</span>
                  <span class="badge bg-warning text-dark">{{
                    stats.temperature?.warm || 0
                  }}</span>
                </div>
              </div>
            </div>

            <div
              class="d-flex border border-info align-items-center gap-3 p-2 px-3 bg-info bg-opacity-10 rounded-3"
            >
              <div class="fs-4 text-info"><i class="bi bi-snow"></i></div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="small fw-bold text-info">COLD (Minat Rendah)</span>
                  <span class="badge bg-info text-dark">{{
                    stats.temperature?.cold || 0
                  }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Rincian 3: Status Pembayaran & Sisa Piutang -->
        <div class="col-lg-4">
          <div
            class="card rounded-4 p-4 h-100 d-flex flex-column justify-content-between custom-card"
          >
            <h6 class="fw-bold mb-3 text-start">Status Pembayaran Transaksi</h6>

            <div class="d-flex justify-content-around text-center my-auto py-2">
              <div>
                <h2 class="fw-bold text-success mb-1">{{ stats.payment?.lunas || 0 }}</h2>
                <span
                  class="badge bg-success-subtle text-success border border-success-subtle"
                >
                  Transaksi Lunas
                </span>
              </div>
              <div class="border-end"></div>
              <div>
                <h2 class="fw-bold text-primary mb-1">{{ stats.payment?.dp || 0 }}</h2>
                <span
                  class="badge bg-primary-subtle text-primary border border-primary-subtle"
                >
                  Transaksi DP
                </span>
              </div>
            </div>

            <div class="mt-3 p-3 bg-light rounded-3 text-start small border">
              <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">Sisa Piutang Belum Lunas:</span>
                <strong
                  :class="
                    (stats.remaining_bill || 0) > 0 ? 'text-danger' : 'text-success'
                  "
                  :title="formatFullCurrency(stats.remaining_bill)"
                >
                  {{ formatShortCurrency(stats.remaining_bill) }}
                </strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </app-layout>
</template>
<style scoped>
.custom-card {
  border: 1px solid #dee2e6;
  transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
}

.custom-card:hover {
  border-color: #0d6efd;
  transform: translateY(-2px);
  box-shadow: 0 5px 12px rgba(0, 0, 0, 0.08);
}
</style>
