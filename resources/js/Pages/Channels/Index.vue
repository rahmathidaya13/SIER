<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { useFilter } from "@/Composables/useFilter";
import { useConfirm } from "@/Composables/useConfirm";
import { highlight } from "@/Helpers/Highlight";
import { formatDate } from "@/Helpers/formatDate";
import { canAccess } from "@/Composables/useAuth";
import FormModal from "./FormModal.vue";
const confirm = useConfirm();

const props = defineProps({
  channels: Object,
  filters: Object,
});

const getCurrentMonth = () => {
  const today = new Date();
  const year = today.getFullYear();

  const month = String(today.getMonth() + 1).padStart(2, "0");
  return `${year}-${month}`;
};

console.log(props.channels.data);
const showModal = ref(false);
const selectedChannel = ref(null);
const openCreateModal = () => {
  showModal.value = true;
  selectedChannel.value = null;
};
const openEditModal = async (data) => {
  showModal.value = true;
  selectedChannel.value = data;
};
const { filters, isLoading } = useFilter({
  routeName: "channel.index",
  initialFilters: {
    keyword: props.filters.keyword ?? "",
    limit: props.filters.limit ?? 10,
    order_by: props.filters.order_by ?? "latest",
    periode: props.filters.periode ?? getCurrentMonth(),
    page: props.filters.page ?? 1,
  },
  watchFields: ["keyword", "limit", "order_by", "periode"],
  only: ["channels", "filters"],
  debounceTime: 500,
});

const formattedPeriode = computed(() => {
  if (!filters.periode) return "";
  const [year, month] = filters.periode.split("-");
  return new Date(Number(year), Number(month) - 1, 1).toLocaleDateString("id-ID", {
    month: "long",
    year: "numeric",
  });
});
console.log(formattedPeriode.value);
const header = [
  {
    label: "No",
    key: "__index",
    attrs: {
      class: "text-center td-number",
      style: { width: "5%" },
    },
  },
  {
    label: "SUMBER / PLATFORM",
    key: "platform",
    attrs: {
      class: "text-start",
      style: { width: "24%" },
    },
  },
  {
    label: "LEAD MASUK",
    key: "total_leads",
    attrs: {
      class: "text-center",
      style: { width: "13%" },
    },
  },
  {
    label: "CLOSING (DEAL)",
    key: "total_deals",
    attrs: {
      class: "text-center",
      style: { width: "14%" },
    },
  },
  {
    label: "RASIO KONVERSI (%)",
    key: "conversion_rate",
    attrs: {
      style: { width: "18%" },
      class: "text-center",
    },
  },
  {
    label: "DIBUAT OLEH",
    key: "creator",
    attrs: {
      class: "text-center",
      style: { width: "14%" },
    },
  },
  {
    label: "Aksi",
    key: "actions",
    attrs: {
      class: "text-center",
      style: { width: "12%" },
    },
  },
];

const getProgressColor = (rate) => {
  if (rate >= 50) return "bg-c-success"; // Sangat Bagus (>= 50%)
  if (rate >= 20) return "bg-c-info"; // Normal (20% - 49%)
  if (rate > 0) return "bg-c-warning"; // Perlu Evaluasi (1% - 19%)
  return "bg-c-danger"; // Mati / 0%
};
// Sensor Cerdas untuk Kesimpulan Otomatis
const bestChannel = computed(() => {
  const channels = props.channels?.data || [];
  const activeChannels = channels.filter((ch) => Number(ch.total_leads) > 0);

  if (activeChannels.length === 0) return null;

  const maxRate = Math.max(...activeChannels.map((ch) => Number(ch.conversion_rate)));
  if (maxRate <= 0) return null;

  const topChannels = activeChannels.filter((c) => Number(c.conversion_rate) === maxRate);
  return {
    names: topChannels.map((c) => c.name).join(", "),
    rate: maxRate,
    isMultiple: topChannels.length > 1,
  };
});
console.log(bestChannel.value);
// Palette warna RGBA (Opacity 0.85 untuk warna dasar)
const chartColors = [
  "rgba(25, 135, 84, 0.85)", // Hijau (Success)
  "rgba(220, 53, 69, 0.85)", // Merah (Danger)
  "rgba(13, 110, 253, 0.85)", // Biru (Primary)
  "rgba(255, 193, 7, 0.85)", // Kuning (Warning)
  "rgba(111, 66, 193, 0.85)", // Ungu (Purple)
  "rgba(13, 202, 240, 0.85)", // Cyan (Info)
  "rgba(33, 37, 41, 0.85)", // Gelap (Dark)
];

// Palette warna RGBA (Opacity 1 untuk efek Hover)
const chartHoverColors = [
  "rgba(25, 135, 84, 1)",
  "rgba(220, 53, 69, 1)",
  "rgba(13, 110, 253, 1)",
  "rgba(255, 193, 7, 1)",
  "rgba(111, 66, 193, 1)",
  "rgba(13, 202, 240, 1)",
  "rgba(33, 37, 41, 1)",
];

// 1. Labels
const channelLabels = computed(() => {
  return (props.channels?.data || []).map((item) => item.name);
});

// 2. Datasets
const channelChartData = computed(() => {
  const list = props.channels?.data || [];
  return [
    {
      label: "Total Leads Masuk",
      data: list.map((item) => item.total_leads),
      backgroundColor: list.map((_, index) => chartColors[index % chartColors.length]),
      hoverBackgroundColor: list.map(
        (_, index) => chartHoverColors[index % chartHoverColors.length]
      ),
      borderWidth: 4,
      borderColor: "#ffffff",
      hoverOffset: 12,
    },
  ];
});

const deleted = async (nameRoute, data) => {
  const setConfirm = await confirm.ask({
    title: `Hapus ${data.name} ?`,
    message: `Data yang sudah dihapus tidak dapat dikembalikan!`,
    confirmText: "Ya, Hapus",
    variant: "danger",
  });

  if (setConfirm) {
    router.delete(route(nameRoute, data.id_channel), {
      preserveScroll: false,
      replace: true,
      preserveState: true,
    });
  }
};

const getRoleName = (roleName) => {
  const roles = {
    super_admin: {
      label: "Head Office",
      color: "bg-dark text-white",
    },
    supervisor: {
      label: "Supervisor",
      color: "bg-primary text-white",
    },
    admin: {
      label: "Admin",
      color: "bg-info text-dark",
    },
    sales: {
      label: "Sales",
      color: "bg-success text-white",
    },
    teknisi: {
      label: "Teknisi",
      color: "bg-secondary text-white",
    },
  };
  return roles[roleName] || "bg-light text-dark border";
};
</script>
<template>
  <Head title="Performa Trafik Channel" />
  <app-layout>
    <template #content>
      <div class="row g-3 view-section">
        <toast :duration="10" />

        <div class="col-12 col-xl-8">
          <div class="card border-0 rounded-4 overflow-hidden">
            <div class="card-header p-4 bg-white">
              <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
              >
                <div>
                  <h5 class="fw-bold mb-1">Performa Akusisi per Channel</h5>
                  <small class="text-muted"
                    >Mengetahui dari mana prospek paling berkualitas datang</small
                  >
                </div>
                <button
                  v-if="canAccess('channel-create')"
                  @click.prevent="openCreateModal"
                  class="btn btn-c-primary px-4 py-2"
                >
                  <i class="bi bi-plus-circle me-1"></i> Tambah Channel
                </button>
              </div>
            </div>

            <div class="row p-3 g-2 align-items-center">
              <div class="col-12 col-xl-6">
                <div class="position-relative">
                  <i class="bi bi-search input-icon-left"></i>
                  <input-text
                    ref="keywordRef"
                    :isValid="false"
                    v-model="filters.keyword"
                    inputClass="input-fixed-height input-height-1 rounded-pill"
                    placeholder="Cari channel atau kode channel..."
                  />
                </div>
              </div>

              <div class="col-xl-2 col-4">
                <input-text
                  :isValid="false"
                  type="month"
                  name="periode"
                  v-model="filters.periode"
                />
              </div>

              <div class="col-xl-2 col-4">
                <select-input
                  :isValid="false"
                  name="limit"
                  v-model="filters.limit"
                  text="Pilih Batas Data"
                  :options="[
                    { label: '☰ 10 Data', value: 10 },
                    { label: '☰ 25 Data', value: 25 },
                    { label: '☰ 50 Data', value: 50 },
                    { label: '☰ 100 Data', value: 100 },
                  ]"
                />
              </div>
              <div class="col-xl-2 col-4">
                <select-input
                  :isValid="false"
                  name="order_by"
                  v-model="filters.order_by"
                  text="Pilih Urutan"
                  :options="[
                    { label: '🔼 Terbaru', value: 'latest' },
                    { label: '🔽 Terlama', value: 'oldest' },
                  ]"
                />
              </div>
            </div>

            <div class="card-body p-0">
              <table-wrapper
                :loader="isLoading"
                loaderText="Sedang memuat data..."
                :headers="header"
                :items="channels?.data"
                rowKey="id_channel"
              >
                <template #empty>
                  <div class="text-center py-5">
                    <i class="bi bi-funnel fa-3x text-muted opacity-25 mb-3 d-block"></i>
                    <h6 class="text-muted fw-bold">Data Channel Tidak Ditemukan</h6>
                    <p class="text-muted small mb-0">
                      Belum ada data channel atau tidak sesuai dengan kata kunci pencarian
                      Anda.
                    </p>
                  </div>
                </template>

                <template #row="{ item, index }">
                  <td class="td-number align-middle">
                    {{ index + 1 + (channels?.current_page - 1) * channels?.per_page }}
                  </td>

                  <td class="text-start align-middle">
                    <div class="d-flex align-items-center gap-3">
                      <div
                        class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width: 40px; height: 40px"
                      >
                        <i class="fs-5" :class="item.icon || 'bi-box'"></i>
                      </div>
                      <div>
                        <div
                          class="fw-bold text-dark"
                          v-html="highlight(item.name, filters.keyword)"
                        ></div>
                        <div class="text-muted small">
                          Kode:
                          <span
                            class="fw-semibold"
                            v-html="highlight(item.code, filters.keyword)"
                          ></span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <td class="text-center align-middle">
                    <span class="badge bg-c-light text-dark border px-3 py-2 shadow-sm">
                      {{ item.total_leads }}
                    </span>
                  </td>

                  <td class="text-center align-middle">
                    <span class="badge bg-c-success px-3 py-2 shadow-sm">
                      <i class="bi bi-check-circle-fill me-1"></i> {{ item.closed_leads }}
                    </span>
                  </td>

                  <td class="align-middle">
                    <div class="d-flex align-items-center gap-2">
                      <div class="progress flex-grow-1" style="height: 8px">
                        <div
                          class="progress-bar rounded-pill"
                          role="progressbar"
                          :class="getProgressColor(item.conversion_rate)"
                          :style="{ width: item.conversion_rate + '%' }"
                          :aria-valuenow="item.conversion_rate"
                          aria-valuemin="0"
                          aria-valuemax="100"
                        ></div>
                      </div>
                      <div
                        class="fw-bold text-dark text-end"
                        style="width: 48px; font-size: 13px"
                      >
                        {{ item.conversion_rate }}%
                      </div>
                    </div>
                  </td>

                  <!-- Kolom Audit Trail -->
                  <td class="text-center align-middle">
                    <div class="d-flex flex-column align-items-center">
                      <span
                        class="fw-bold text-dark text-capitalize"
                        style="font-size: 14px"
                      >
                        {{ item.creator_name || "Sistem" }}
                      </span>
                      <span
                        v-if="item.creator_role_name"
                        class="badge bg-secondary-subtle text-secondary border border-secondary-subtle mt-1"
                        style="font-size: 10px"
                      >
                        {{ getRoleName(item.creator_role_name).label }}
                      </span>
                      <div class="d-flex align-items-center gap-0 flex-column mt-1">
                        <small
                          class="text-muted"
                          style="font-size: 11px"
                          :title="'Dibuat oleh: ' + (item.creator_name || '-')"
                        >
                          <i class="bi bi-clock text-primary fw-bold"></i>
                          {{ formatDate(item.created_at) }}
                        </small>
                        <small
                          v-if="item.created_at !== item.updated_at"
                          class="text-muted"
                          style="font-size: 11px"
                          :title="'Diperbarui oleh: ' + (item.updater_name || '-')"
                        >
                          <i class="bi bi-pencil text-success fw-bold"></i>
                          {{ formatDate(item.updated_at) }}
                        </small>
                      </div>
                    </div>
                  </td>

                  <td class="text-center align-middle pe-4 py-3">
                    <div
                      v-if="
                        canAccess('channel-edit', item, { strictOwner: true }) ||
                        canAccess('channel-delete', item, { strictOwner: true })
                      "
                      class="d-flex gap-1 justify-content-center"
                    >
                      <button
                        v-if="canAccess('channel-edit', item, { strictOwner: true })"
                        @click.prevent="openEditModal(item)"
                        type="button"
                        class="btn btn-c-slate"
                        title="Edit Channel"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        v-if="canAccess('channel-delete', item, { strictOwner: true })"
                        @click.prevent="deleted('channel.delete', item)"
                        type="button"
                        class="btn btn-c-danger"
                        title="Hapus Channel"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                    <div v-else>
                      <span class="badge badge-soft-info rounded-3 py-1">
                        <i class="bi bi-lock-fill"></i>
                        No Action
                      </span>
                    </div>
                  </td>
                </template>

                <template #pagination>
                  <div
                    class="border-top py-2 px-3 d-flex flex-column flex-md-row justify-content-between align-items-center"
                  >
                    <small class="text-muted mb-2 mb-md-0">
                      Menampilkan <strong>{{ channels?.from ?? 0 }}</strong> -
                      <strong>{{ channels?.to ?? 0 }}</strong> dari
                      <strong>{{ channels?.total ?? 0 }}</strong>
                    </small>
                    <pagination
                      size="pagination-sm"
                      :links="channels?.links"
                      routeName="channel.index"
                      :additionalQuery="{
                        keyword: filters.keyword,
                        limit: filters.limit,
                        order_by: filters.order_by,
                        periode: filters.periode,
                      }"
                      :only="['channels', 'filters']"
                    />
                  </div>
                </template>
              </table-wrapper>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-4 order-first order-xl-last">
          <div class="card border-0 rounded-4 overflow-hidden">
            <div class="card-header p-4 bg-white">
              <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
              >
                <div>
                  <h5 class="fw-bold mb-1">Statistik Trafik {{ formattedPeriode }}</h5>
                  <small class="text-muted"
                    >Persentase distribusi sebaran prospek masuk</small
                  >
                </div>
              </div>
            </div>

            <div class="py-3">
              <chart-bar
                type="pie"
                :height="280"
                :labels="channelLabels"
                :datasets="channelChartData"
                :hide-scales="true"
                tooltip-suffix=" Leads"
              />
            </div>

            <div class="p-3 bg-light rounded-3 text-start small text-muted border-0">
              💡 <b>Kesimpulan Otomatis:</b>
              <span v-if="bestChannel">
                Performa terbaik saat ini dipegang oleh channel
                <b>{{ bestChannel.names }}</b> dengan rasio closing sebesar
                <b>{{ bestChannel.rate }}%</b>. Pertahankan stabilitas alokasi di
                {{ bestChannel.isMultiple ? "channel-channel" : "channel" }} ini.
              </span>

              <span v-else>
                Belum ada aktivitas konversi data transaksi yang berhasil untuk ditarik
                kesimpulan.
              </span>
            </div>
          </div>
        </div>
      </div>
      <FormModal
        :channel="selectedChannel"
        :show="showModal"
        @update:show="(val) => (showModal = val)"
      />
    </template>
  </app-layout>
</template>
