<script setup>
import { Form, Head, usePage, router, Link } from "@inertiajs/vue3";
import { computed, onMounted, reactive, ref } from "vue";
import { useFilter } from "@/Composables/useFilter";
import { useConfirm } from "@/Composables/useConfirm";
import { highlight } from "@/Helpers/Highlight";
import axios from "axios";
import { formatDate } from "@/Helpers/formatDate";
import { currencyFormat } from "@/Helpers/currencyFormat";
import PaymentModal from "./PaymentModal.vue";
import PaymentCancelModal from "./PaymentCancelModal.vue";
import ActivityTimelineModal from "./ActivityTimelineModal.vue";
import { hasRole, hasPermission, canAccess } from "@/Composables/useAuth";

const props = defineProps({
  sales: Object,
  filters: Object,
  stats: Object,
  branch: Array,
});
const page = usePage();
const canSelectBranch = computed(() => hasRole(["super_admin", "developer"]));
const branchName = computed(() => {
  const user = page.props.auth?.user;
  return (
    user?.branch?.name ||
    props.branch?.find((b) => b.id_branch === user?.id_branch)?.name ||
    "Cabang Tidak Diketahui"
  );
});
console.log(branchName.value);

const { filters, isLoading } = useFilter({
  routeName: "sales.index",
  initialFilters: {
    keyword: props.filters.keyword ?? "",
    limit: props.filters.limit ?? 10,
    order_by: props.filters.order_by ?? "latest",
    branch: !canSelectBranch.value ? props.filters.branch ?? branchName.value : null,
    page: props.filters.page ?? 1,
    status: props.filters.status ?? "closing",
  },
  watchFields: ["keyword", "limit", "order_by", "branch", "status"],
  only: ["sales", "filters", "stats", "branch"],
  debounceTime: 500,
});

const showPaymentModal = ref(false);
const showPaymentCancelModal = ref(false);
const showActivityModal = ref(false);
const selectedIdSale = ref(null);
const openPaymentModal = (data) => {
  selectedIdSale.value = data.id_sales;
  showPaymentModal.value = true;
};
const openActivityModal = (data) => {
  selectedIdSale.value = data.id_sales;
  showActivityModal.value = true;
};

const cancelClosing = (data) => {
  selectedIdSale.value = data.id_sales;
  showPaymentCancelModal.value = true;
};
const header = [
  {
    label: "NO",
    key: "__index",
    attrs: {
      style: { width: "4%" },
      class: "text-center td-number",
    },
  },
  {
    label: "INVOICE & TANGGAL",
    key: "invoice_date",
    attrs: {
      style: { width: "14%" },
      class: "text-start ps-4", // Sejajar dengan <td class="ps-4"> pada baris invoice
    },
  },
  {
    label: "NAMA PELANGGAN",
    key: "customer",
    attrs: {
      style: { width: "18%" },
      class: "text-start",
    },
  },
  {
    label: "PRODUK DIBELI",
    key: "product",
    attrs: {
      style: { width: "20%" },
      class: "text-start", // Rata kiri agar sejajar dengan badge nama mesin
    },
  },
  {
    label: "HARGA DEAL",
    key: "deal_prices",
    attrs: {
      style: { width: "12%" },
      class: "text-end",
    },
  },
  {
    label: "STATUS BAYAR",
    key: "payment_status",
    attrs: {
      style: { width: "12%" },
      class: "text-center",
    },
  },
  {
    label: "DIBUAT OLEH",
    key: "creator",
    attrs: {
      style: { width: "13%" },
      class: "text-start", // Sejajar dengan avatar & nama pembuat di <td>
    },
  },
  {
    label: "AKSI",
    key: "actions",
    attrs: {
      style: { width: "7%" },
      class: "text-center",
    },
  },
];
const payActions = [
  {
    label: "Lakukan pembayaran",
    action: "pay",
    protectedSelf: true,
    icon: "bi bi-wallet2",
    title: "Lakukan pembayaran",
    class: "btn btn-c-success",
    show: (item) => item.payment_status !== "LUNAS",
  },
  {
    label: "Batal pembayaran",
    action: "cancelClosing",
    protectedSelf: true,
    icon: "bi bi-x-circle-fill",
    title: "Batal pembayaran",
    class: "btn btn-c-danger",
  },
];

const branchOptions = computed(() => {
  const branches = props.branch ?? [];
  // Super Admin & Developer: tampilkan semua cabang
  if (canSelectBranch.value) {
    return [
      {
        label: "Semua Cabang",
        value: "all",
      },
      ...branches.map((data) => ({
        label: data.name,
        value: data.name,
      })),
    ];
  }

  // User Cabang: tampilkan cabang user
  const userBranchId = page.props.auth?.user?.id_branch;
  return branches
    .filter((data) => data.id_branch === userBranchId)
    .map((data) => ({
      label: data.name,
      value: data.name,
    }));
});
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
const keywordRef = ref(null);
onMounted(() => {
  keywordRef.value.focus();
});

const rowClasses = (item) => ({
  "tr-highlight-danger": item.status === "CANCELLED",
});
const getWaLink = (phone) => {
  let p = phone.replace(/\D/g, ""); // Hapus spasi, strip, tanda plus
  if (p.startsWith("0")) p = "62" + p.slice(1);
  return `https://wa.me/${p}`;
};
const getEmail = (email) => {
  return `mailto:${email}`;
};

const cancelTagMeta = (tag) => {
  const map = {
    CUSTOMER_CANCELLED: {
      label: "Pelanggan Membatalkan Pesanan",
    },
    PAYMENT_FAILED: {
      label: "Pembayaran Tidak Diselesaikan",
    },
    ORDER_UNFULFILLABLE: {
      label: "Pesanan Tidak Dapat Dipenuhi",
    },
    DATA_ERROR: {
      label: "Kesalahan Input Transaksi",
    },
    DUPLICATE: {
      label: "Transaksi Duplikat",
    },
    OTHER: {
      label: "Lainnya",
    },
  };
  return map[tag] || map.OTHER;
};
</script>
<template>
  <Head title="Rekap Closing" />
  <app-layout>
    <template #content>
      <div class="row g-3 view-section">
        <div class="col-12">
          <toast :duration="10" />

          <div class="row g-3 mb-4">
            <div class="col-md-12 col-12 col-xl-4">
              <div class="card p-3 bg-success text-white border shadow-sm rounded-3">
                <small class="text-white-50 fw-medium">Omset Masuk (Verified)</small>
                <h3 class="fw-bold mb-0 mt-1">{{ currencyFormat(stats.omset_lunas) }}</h3>
                <div class="border-top border-light border-opacity-25 mt-3 pt-2">
                  <small class="text-white-50" style="font-size: 12px">
                    <i class="bi bi-info-circle me-1"></i> Total akumulasi dana yang telah
                    dibayar.
                  </small>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-12 col-xl-4">
              <div
                class="card p-3 bg-warning-subtle text-dark border border-warning shadow-sm rounded-3"
              >
                <small class="text-muted fw-medium">Piutang / Menunggu Pelunasan</small>
                <h3 class="fw-bold mb-0 mt-1 text-warning-emphasis">
                  {{ currencyFormat(stats.piutang) }}
                </h3>
                <div class="border-top border-warning border-opacity-25 mt-3 pt-2">
                  <small class="text-muted" style="font-size: 12px">
                    <i class="bi bi-info-circle me-1"></i> Σ (Nilai Harga Deal Keseluruhan
                    - Dana Masuk)
                  </small>
                </div>
              </div>
            </div>
            <div class="col-md-6 col-12 col-xl-4">
              <div class="card p-3 bg-white border shadow-sm rounded-3">
                <small class="text-muted fw-medium"
                  >Rata-rata Nilai Deal (Ticket Size)</small
                >
                <h3 class="fw-bold mb-0 mt-1 text-dark">
                  {{ currencyFormat(stats.avg_deal) }}
                </h3>
                <div class="border-top mt-3 pt-2">
                  <small class="text-muted" style="font-size: 12px">
                    <i class="bi bi-info-circle me-1"></i> Σ Total Nilai Harga Deal ÷
                    Jumlah Transaksi
                  </small>
                </div>
              </div>
            </div>
          </div>

          <div class="card border-0 rounded-4 overflow-hidden">
            <div class="card-header p-4 bg-white">
              <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
              >
                <div>
                  <h5 class="fw-bold mb-1">Data Penjualan & Invoice</h5>
                  <small class="text-muted"
                    >Manajemen transaksi masuk dan pelunasan tagihan pelanggan.</small
                  >
                </div>
                <div class="dropdown" v-if="canAccess('data-export')">
                  <button
                    data-bs-toggle="dropdown"
                    class="btn btn-c-slate dropdown-toggle"
                    type="button"
                  >
                    <i class="bi bi-download me-2"></i>
                    Export Data
                  </button>
                  <ul class="dropdown-menu">
                    <li>
                      <a class="dropdown-item text-success" href="#">
                        <i class="bi bi-file-earmark-excel-fill me-1"></i>
                        Unduh Excel
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item text-danger" href="#">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Unduh PDF</a
                      >
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="row p-3 g-2 align-items-center">
              <div class="col-12 col-md-12 col-lg-12 col-xl-8">
                <div class="position-relative">
                  <i class="bi bi-search input-icon-left"></i>
                  <input-text
                    ref="keywordRef"
                    :isValid="false"
                    v-model="filters.keyword"
                    inputClass="input-fixed-height input-height-1 rounded-pill"
                    placeholder="Cari nama pelanggan dan nomor handphone...."
                  />
                </div>
              </div>

              <div class="col-xl-2 col-6 col-md-3 col-lg-3">
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
              <div class="col-xl-2 col-6 col-md-3 col-lg-3">
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

              <div class="col-xl-6 col-6 col-md-3 col-lg-3">
                <select-input
                  :disabled="!canSelectBranch"
                  :isValid="false"
                  name="branch_selected"
                  v-model="filters.branch"
                  text="Pilih Sales Cabang
                  "
                  :options="branchOptions"
                />
              </div>

              <div class="col-xl-6 col-6 col-md-3 col-lg-3">
                <select-input
                  :isValid="false"
                  name="limit"
                  v-model="filters.status"
                  text="Pilih Status"
                  :options="[
                    { label: 'Tampilkan Semua', value: 'all' },
                    { label: 'Transaksi Closing', value: 'closing' },
                    { label: 'Transaksi Dibatalkan', value: 'cancelled' },
                  ]"
                />
              </div>
            </div>

            <div class="card-body p-0">
              <table-wrapper
                :rowClass="rowClasses"
                :loader="isLoading"
                loaderText="Sedang memuat data penjualan..."
                :headers="header"
                :items="sales?.data"
                rowKey="id_sales"
              >
                <template #empty>
                  <div class="text-center py-5">
                    <i
                      class="bi bi-receipt-cutoff fa-3x text-muted opacity-25 mb-3 d-block"
                    ></i>
                    <h6 class="text-muted fw-bold">Data Penjualan Masih Kosong</h6>
                    <p class="text-muted small mb-0">
                      Transaksi yang sudah deal dari prospek (Leads) akan tampil di sini.
                    </p>
                  </div>
                </template>

                <template #row="{ item, index }">
                  <td class="td-number">
                    {{ index + 1 + (sales?.current_page - 1) * sales?.per_page }}
                  </td>

                  <td class="ps-4 align-middle py-2" style="min-width: 170px">
                    <div class="d-flex flex-column align-items-start gap-1">
                      <span
                        class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace fw-bold px-2 py-1 rounded-2"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-receipt me-1"></i>
                        <span
                          v-html="highlight(item.invoice || '-', filters.keyword)"
                        ></span>
                      </span>

                      <span
                        class="text-muted d-flex align-items-center"
                        style="font-size: 11px"
                      >
                        <i class="bi bi-calendar-check me-1 opacity-75"></i>
                        {{ formatDate(item.deal_at) }}
                      </span>

                      <!-- Jika ada catatan (notes) transaksi -->
                      <span
                        v-if="item.notes"
                        class="text-secondary fst-italic text-truncate"
                        style="font-size: 11px; max-width: 180px"
                        :title="item.notes"
                      >
                        <i class="bi bi-sticky me-1"></i>{{ item.notes }}
                      </span>
                    </div>
                  </td>

                  <td class="align-middle py-2" style="min-width: 220px">
                    <div class="d-flex align-items-center mb-1">
                      <div
                        @click.prevent="openActivityModal(item)"
                        class="fw-bold text-primary text-decoration-none cursor-pointer lh-sm"
                        style="font-size: 14px"
                        title="Klik untuk lihat riwayat aktivitas"
                        v-html="
                          highlight(item.customer_name || 'Tanpa Nama', filters.keyword)
                        "
                      ></div>
                    </div>

                    <!-- Email Pelanggan -->
                    <div v-if="item.customer_email" class="mb-1" style="font-size: 11px">
                      <a
                        :href="getEmail(item.customer_email)"
                        class="text-muted text-decoration-none d-inline-flex align-items-center hover-primary"
                      >
                        <i class="bi bi-envelope-at me-1 text-secondary"></i>
                        <span
                          v-html="highlight(item.customer_email, filters.keyword)"
                        ></span>
                      </a>
                    </div>

                    <!-- Nomor WhatsApp Pelanggan (Diperbaiki ke item.customer_phone) -->
                    <div class="d-flex align-items-center gap-2">
                      <a
                        v-if="item.customer_phone"
                        :href="getWaLink(item.customer_phone)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 text-decoration-none px-2 py-1 rounded-pill d-inline-flex align-items-center gap-1 fw-semibold"
                        style="font-size: 11px"
                        title="Chat langsung via WhatsApp"
                      >
                        <i class="bi bi-whatsapp"></i>
                        <span
                          v-html="highlight(item.customer_phone, filters.keyword)"
                        ></span>
                      </a>
                      <span
                        v-else
                        class="badge bg-light text-muted border rounded-pill px-2 py-1"
                        style="font-size: 11px"
                      >
                        No Phone
                      </span>
                    </div>
                  </td>

                  <td
                    class="align-middle py-2"
                    style="min-width: 240px; max-width: 320px"
                  >
                    <div
                      v-if="item.sales_product && item.sales_product.length > 0"
                      class="d-flex flex-column gap-1"
                    >
                      <template
                        v-for="(slp, idx) in item.sales_product.slice(0, 2)"
                        :key="slp.product?.id_product || idx"
                      >
                        <span
                          v-if="slp.product"
                          class="badge bg-light text-dark border border-secondary border-opacity-25 d-inline-flex align-items-center py-1 px-2 rounded-3 text-start shadow-none"
                          style="font-size: 12px; max-width: 290px"
                          :title="slp.product.name"
                        >
                          <i
                            class="bi bi-box-seam-fill text-primary me-2 flex-shrink-0"
                          ></i>
                          <span class="text-truncate fw-medium">{{
                            slp.product.name
                          }}</span>
                        </span>
                      </template>

                      <span
                        v-if="item.sales_product.length > 2"
                        class="badge bg-secondary bg-opacity-10 text-secondary border d-inline-flex align-items-center align-self-start py-1 px-2 rounded-pill cursor-pointer"
                        style="font-size: 11px"
                        :title="
                          item.sales_product
                            .slice(2)
                            .map((p) => p.product?.name)
                            .join('\n')
                        "
                      >
                        <strong class="me-1">+{{ item.sales_product.length - 2 }}</strong>
                        Produk Lainnya
                      </span>
                    </div>

                    <div v-else>
                      <span
                        class="text-muted fst-italic d-inline-flex align-items-center"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-dash-circle me-1 opacity-50"></i> Belum ada produk
                      </span>
                    </div>
                  </td>

                  <td class="text-end align-middle py-2" style="min-width: 150px">
                    <div class="fw-bold text-dark" style="font-size: 14px">
                      {{ currencyFormat(item.deal_price) }}
                    </div>
                    <div
                      v-if="item.paid_amount > 0 && item.paid_amount < item.deal_price"
                      class="text-success fw-medium mt-1"
                      style="font-size: 11px"
                    >
                      Masuk: {{ currencyFormat(item.paid_amount) }}
                    </div>
                  </td>

                  <td class="text-center align-middle py-2" style="min-width: 180px">
                    <div
                      v-if="item.status === 'CANCELLED'"
                      class="d-flex flex-column align-items-center"
                    >
                      <span
                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-1 rounded-pill fw-semibold"
                        style="font-size: 11.5px"
                        :title="item.cancelled_notes || item.cancelled_reason"
                      >
                        <i class="bi bi-x-octagon-fill me-1"></i> VOID / BATAL
                      </span>

                      <small
                        v-if="item.cancelled_reason"
                        class="text-muted mt-1 text-truncate"
                        style="font-size: 11.5px; max-width: 290px"
                        :title="item.cancelled_notes || item.cancelled_reason"
                      >
                        Alasan: {{ cancelTagMeta(item.cancelled_reason).label }}
                      </small>
                    </div>

                    <!-- B. Jika Pembayaran Sudah LUNAS -->
                    <div
                      v-else-if="item.payment_status === 'LUNAS'"
                      class="d-inline-flex flex-column align-items-center"
                    >
                      <span
                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill fw-semibold"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-check-circle-fill me-1"></i> LUNAS
                      </span>
                    </div>

                    <!-- C. Jika Masih DP / Belum Lunas -->
                    <div v-else class="d-flex flex-column px-2">
                      <div
                        class="d-flex justify-content-between align-items-center gap-2 mb-1"
                      >
                        <span
                          class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-2 py-1 rounded-pill fw-semibold"
                          style="font-size: 11px"
                        >
                          <i class="bi bi-hourglass-split me-1"></i>
                          {{ item.payment_status }}
                        </span>
                        <span class="text-danger fw-bold" style="font-size: 11px">
                          Sisa: {{ currencyFormat(item.remaining_bill) }}
                        </span>
                      </div>
                      <div
                        class="progress bg-secondary bg-opacity-10 rounded-pill"
                        style="height: 5px"
                      >
                        <div
                          class="progress-bar bg-success rounded-pill"
                          :style="`width: ${
                            item.deal_price > 0
                              ? Math.min(
                                  100,
                                  Math.round((item.paid_amount / item.deal_price) * 100)
                                )
                              : 0
                          }%`"
                        ></div>
                      </div>
                    </div>
                  </td>

                  <td class="align-middle text-start py-2" style="min-width: 210px">
                    <div class="d-flex align-items-start">
                      <div
                        class="client-avatar me-2 bg-gradient-light border text-primary fw-bold small d-flex align-items-center justify-content-center shadow-sm flex-shrink-0 mt-1"
                      >
                        {{
                          item.creator_name
                            ? item.creator_name.substring(0, 2).toUpperCase()
                            : "NA"
                        }}
                      </div>

                      <div class="d-flex flex-column small" style="line-height: 1.35">
                        <!-- Nama Creator & Role Badge -->
                        <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                          <span class="fw-bold text-dark text-capitalize">
                            {{ item.creator_name || "Sistem" }}
                          </span>
                          <span
                            v-if="item.creator_role_name"
                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded-pill px-1 py-0 fw-medium text-capitalize"
                            style="font-size: 11px"
                          >
                            {{
                              getRoleName(item.creator_role_name)?.label ??
                              item.creator_role_name
                            }}
                          </span>
                        </div>

                        <!-- Nama Cabang -->
                        <span
                          class="mb-1 fw-semibold text-secondary"
                          style="font-size: 11px"
                        >
                          <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                          {{ item.branch_name || "Pusat" }}
                        </span>

                        <!-- Waktu Dibuat & Diubah -->
                        <div
                          class="text-muted d-flex flex-column gap-1"
                          style="font-size: 11px"
                        >
                          <span>
                            <i class="bi bi-plus-circle me-1 opacity-75"></i>
                            {{ formatDate(item.created_at) }}
                          </span>

                          <span
                            v-if="item.updated_at && item.updated_at !== item.created_at"
                          >
                            <i class="bi bi-pencil-square me-1 text-warning"></i>
                            {{ formatDate(item.updated_at) }}
                            <template
                              v-if="
                                item.updated_by &&
                                item.updated_by !== item.created_by &&
                                item.updater_name
                              "
                            >
                              oleh
                              <strong class="text-dark text-capitalize">{{
                                item.updater_name
                              }}</strong>
                            </template>
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <td class="text-center align-middle py-2">
                    <div
                      v-if="item.status === 'CANCELLED'"
                      class="d-flex justify-content-center"
                    >
                      <span
                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1"
                        style="font-size: 11px"
                      >
                        <i class="bi bi-x-circle-fill me-1"></i> Dibatalkan
                      </span>
                    </div>

                    <!-- B. Jika Transaksi Sudah LUNAS (Terkunci untuk Cabang, kecuali Super Admin/Dev jika ingin membatalkan) -->
                    <div
                      v-else-if="item.payment_status === 'LUNAS'"
                      class="d-flex justify-content-center align-items-center gap-1"
                    >
                      <button
                        v-if="
                          canAccess('closing-payment_cancel', item, { strictOwner: true })
                        "
                        @click="cancelClosing(item)"
                        class="btn btn-c-danger"
                        title="Batalkan Transaksi Lunas"
                      >
                        <i class="bi bi-x-circle"></i>
                      </button>
                      <span
                        v-else
                        class="small fw-semibold badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2 py-1"
                      >
                        <i class="bi bi-shield-lock-fill me-1"></i> Terverifikasi
                      </span>
                    </div>

                    <!-- C. Jika Masih DP / Belum Lunas: Cek Hak Akses & Hierarki via canAccess -->
                    <div
                      v-else-if="
                        canAccess('closing-remaining', item, { strictOwner: true })
                      "
                      class="d-flex justify-content-center align-items-center gap-1"
                    >
                      <button
                        @click="openPaymentModal(item)"
                        class="btn btn-c-success"
                        title="Lunasi / Bayar Sisa Tagihan"
                      >
                        <i class="bi bi-cash-coin"></i>
                      </button>

                      <button
                        @click="cancelClosing(item)"
                        class="btn btn-c-danger"
                        title="Batalkan Transaksi Closing"
                      >
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </div>

                    <!-- D. Jika Tidak Punya Akses / Milik Atasan atau Sales Lain -->
                    <div v-else class="d-flex justify-content-center">
                      <span
                        class="small fw-semibold badge badge-soft-info rounded-5 d-inline-flex align-items-center gap-1"
                      >
                        <i class="bi bi-lock-fill"></i>
                        No Action
                      </span>
                    </div>
                  </td>
                </template>

                <template #pagination>
                  <div
                    v-if="sales.data.length > 0"
                    class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center"
                  >
                    <small class="text-muted mb-2 mb-md-0">
                      Menampilkan <strong>{{ sales.from ?? 0 }}</strong> -
                      <strong>{{ sales.to ?? 0 }}</strong> dari
                      <strong>{{ sales.total ?? 0 }}</strong>
                    </small>
                    <pagination
                      size="pagination-sm"
                      :links="sales?.links"
                      routeName="sales.index"
                      :additionalQuery="{
                        keyword: filters.keyword,
                        limit: filters.limit,
                        order_by: filters.order_by,
                        branch: filters.branch,
                        status: filters.status,
                      }"
                      :only="['sales', 'filters', 'stats', 'branch']"
                    />
                  </div>
                </template>
              </table-wrapper>
            </div>
          </div>
        </div>
      </div>
      <PaymentCancelModal
        :idSales="selectedIdSale"
        :show="showPaymentCancelModal"
        @update:show="showPaymentCancelModal = $event"
      />
      <PaymentModal
        :idSales="selectedIdSale"
        :show="showPaymentModal"
        @update:show="showPaymentModal = $event"
      />
      <ActivityTimelineModal
        :idSales="selectedIdSale"
        :show="showActivityModal"
        @update:show="showActivityModal = $event"
      />
    </template>
  </app-layout>
</template>
