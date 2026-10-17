<script setup>
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import { useFilter } from "@/Composables/useFilter";
import { formatDate } from "@/Helpers/formatDate";
import FormModal from "./FormModal.vue";

const props = defineProps({
  customers: Object,
  filters: Object,
});

const header = [
  {
    label: "No",
    key: "__index",
    attrs: {
      class: "font-medium td-number",
    },
  },
  {
    label: "Pelanggan",
    key: "customer",
    attrs: {
      class: "font-medium",
    },
  },
  {
    label: "Kontak",
    key: "contact",
    attrs: {
      class: "font-medium",
    },
  },
  {
    label: "Jenis Usaha",
    key: "bussiness_type",
    attrs: {
      class: "font-medium",
    },
  },
  {
    label: "Alamat",
    key: "address",
    attrs: {
      class: "font-medium",
    },
  },
  {
    label: "Terdaftar",
    key: "created_at",
    attrs: {
      class: "font-medium",
    },
  },
  {
    label: "Aksi",
    key: "actions",
    attrs: {
      class: "font-medium text-center",
    },
  },
];

const { filters, isLoading } = useFilter({
  routeName: "customer.index",
  initialFilters: {
    keyword: props.filters.keyword ?? "",
    limit: props.filters.limit ?? 10,
    order_by: props.filters.order_by ?? "latest",
    page: props.filters.page ?? 1,
  },
  watchFields: ["keyword", "limit", "order_by"],
  only: ["customers", "filters"],
  debounceTime: 500,
});

// Helper untuk mengambil maksimal 2 huruf inisial nama
const getInitials = (name) => {
  if (!name) return "?";
  return name
    .split(" ")
    .map((n) => n[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();
};

// Styling & Label untuk customer_type
const customerTypeMeta = (type) => {
  const map = {
    individual: {
      label: "Perorangan",
      icon: "bi-person",
      class: "bg-primary bg-opacity-10 text-primary border-primary-subtle",
    },
    company: {
      label: "Perusahaan",
      icon: "bi-building",
      class: "bg-info bg-opacity-10 text-info-emphasis border-info-subtle",
    },
    government: {
      label: "Pemerintahan",
      icon: "bi-bank",
      class: "bg-warning bg-opacity-10 text-warning-emphasis border-warning-subtle",
    },
  };
  return map[type] || map.individual;
};

// Styling & Label untuk business_type
const businessTypeMeta = (type) => {
  const map = {
    retail: { label: "Retail / Toko", icon: "bi-shop" },
    fnb: { label: "F&B / Kuliner", icon: "bi-cup-hot" },
    service: { label: "Jasa / Layanan", icon: "bi-tools" },
    manufacture: { label: "Manufaktur", icon: "bi-gear-wide-connected" },
    other: { label: "Lainnya", icon: "bi-grid" },
  };
  return map[type] || map.other;
};
const showModal = ref(false);
const selectedId = ref(null);
const openCreateModal = () => {
  showModal.value = true;
  selectedId.value = null;
};
const openEditModal = (data) => {
  showModal.value = true;
  selectedId.value = data.id_customer;
};
console.log(props.customers);
</script>
<template>
  <Head title="Halaman Pelanggan" />
  <app-layout>
    <template #content>
      <div class="row g-3 view-section">
        <div class="col-12">
          <callout :duration="0" />
          <div class="card border-0 rounded-4 overflow-hidden">
            <div class="card-header p-4 bg-white">
              <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
              >
                <div>
                  <h5 class="fw-bold mb-1">Manajemen Pelanggan</h5>
                </div>
                <button
                  @click.prevent="openCreateModal"
                  class="btn btn-c-primary px-4 py-2"
                >
                  <i class="bi bi-person-plus-fill me-1"></i> Pelanggan Baru
                </button>
              </div>
            </div>

            <div class="card-body p-0">
              <table-wrapper
                :loader="isLoading"
                loaderText="Sedang memuat data..."
                :headers="header"
                :items="customers?.data"
                row-key="id_customer"
              >
                <template #empty>
                  <i class="bi bi-funnel fa-3x text-muted opacity-25 mb-3"></i>
                  <p class="text-muted fw-semibold">Data Pelanggan Masih Kosong</p>
                </template>

                <template #row="{ item, index }">
                  <td class="td-number">
                    {{ index + 1 + (customers?.current_page - 1) * customers?.per_page }}
                  </td>

                  <!-- Info Pelanggan (Avatar + Nama) -->
                  <td class="align-middle">
                    <div class="d-flex align-items-center gap-2">
                      <div
                        class="avatar-circle bg-primary bg-opacity-10 text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 border border-primary border-opacity-10"
                      >
                        {{ getInitials(item.name) }}
                      </div>
                      <div class="min-w-0">
                        <div
                          class="fw-bold text-dark text-capitalize text-truncate"
                          style="max-width: 180px"
                          :title="item.name"
                        >
                          {{ item.name }}
                        </div>
                        <small class="text-muted d-block" style="font-size: 12px">
                          ID: {{ item.id_customer?.substring(0, 8).toUpperCase() }}
                        </small>
                      </div>
                    </div>
                  </td>

                  <!-- Kontak (Telepon & Email) -->
                  <td class="align-middle">
                    <div class="d-flex flex-column gap-1">
                      <a
                        :href="`https://wa.me/${item.phone?.replace(/^0/, '62')}`"
                        target="_blank"
                        class="text-decoration-none text-dark fw-medium d-inline-flex align-items-center gap-2 hover-whatsapp"
                        style="font-size: 14px"
                        title="Hubungi via WhatsApp"
                      >
                        <i class="bi bi-whatsapp text-success"></i>
                        <span>{{ item.phone }}</span>
                      </a>

                      <div
                        v-if="item.email"
                        class="text-muted d-flex align-items-center gap-2"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-envelope text-secondary"></i>
                        <span
                          class="text-truncate"
                          style="max-width: 160px"
                          :title="item.email"
                        >
                          {{ item.email }}
                        </span>
                      </div>
                      <span v-else class="text-muted fst-italic" style="font-size: 12px">
                        Email belum diatur
                      </span>
                    </div>
                  </td>

                  <!-- Tipe Pelanggan & Sektor Bisnis -->
                  <td class="align-middle">
                    <div class="d-flex flex-column align-items-start gap-2">
                      <!-- Badge Customer Type -->
                      <span
                        class="badge border rounded-pill fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1"
                        :class="customerTypeMeta(item.customer_type).class"
                        style="font-size: 14px"
                      >
                        <i
                          class="bi"
                          :class="customerTypeMeta(item.customer_type).icon"
                        ></i>
                        {{ customerTypeMeta(item.customer_type).label }}
                      </span>

                      <!-- Sektor Bisnis -->
                      <span
                        class="text-muted d-inline-flex align-items-center gap-1"
                        style="font-size: 12px"
                      >
                        <i
                          class="bi text-secondary"
                          :class="businessTypeMeta(item.business_type).icon"
                        ></i>
                        <span>{{ businessTypeMeta(item.business_type).label }}</span>
                      </span>
                    </div>
                  </td>

                  <!-- Alamat & Cabang -->
                  <td class="align-middle" style="max-width: 220px">
                    <div class="d-flex flex-column gap-1">
                      <p
                        class="mb-0 text-dark line-clamp-2"
                        style="font-size: 13px; line-height: 1.4"
                        :title="item.address"
                      >
                        {{ item.address || "-" }}
                      </p>
                      <div
                        class="d-flex align-items-center gap-1 text-muted"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-geo-alt-fill text-danger opacity-75"></i>
                        <span class="fw-medium">
                          {{ item.branch_name || "Cabang Utama" }}
                        </span>
                      </div>
                    </div>
                  </td>

                  <!-- 6. Info Pencatatan (Created At & Created By) -->
                  <td class="align-middle">
                    <div class="d-flex flex-column">
                      <span class="text-dark fw-medium" style="font-size: 13px">
                        {{ formatDate(item.created_at) }}
                      </span>
                      <!-- Nama Pembuat (Sesuaikan dengan field relasi/join dari tabel users) -->
                      <small
                        class="text-muted d-flex align-items-center gap-1"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-person-check"></i>
                        <span class="text-truncate" style="max-width: 110px">
                          {{ item.creator_name || "Sistem" }}
                        </span>
                      </small>
                    </div>
                  </td>
                  <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                      <button
                        @click.prevent="openEditModal(item.id_customer)"
                        type="button"
                        class="btn btn-c-slate"
                        title="Edit"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button type="button" class="btn btn-c-danger" title="Hapus">
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                  </td>
                </template>
                <template #pagination>
                  <div
                    class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center"
                  >
                    <small class="text-muted mb-2 mb-md-0">
                      Menampilkan <strong>{{ customers.from ?? 0 }}</strong> -
                      <strong>{{ customers.to ?? 0 }}</strong> dari
                      <strong>{{ customers.total ?? 0 }}</strong>
                    </small>
                    <pagination
                      size="pagination-sm"
                      :links="customers?.links"
                      routeName="customer.index"
                      :additionalQuery="{
                        keyword: filters.keyword,
                        limit: filters.limit,
                        sort_order: filters.order_by,
                      }"
                      :only="['customers', 'filters']"
                    />
                  </div>
                </template>
              </table-wrapper>
            </div>
          </div>
        </div>
      </div>
      <FormModal
        :idCustomer="selectedId"
        :show="showModal"
        @update:show="showModal = $event"
      />
    </template>
  </app-layout>
</template>
<style scoped>
.avatar-circle {
  width: 38px;
  height: 38px;
  font-size: 13px;
  letter-spacing: 0.5px;
}

/* Memotong teks alamat maksimal 2 baris */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Efek Hover Kontak WhatsApp */
.hover-whatsapp:hover {
  color: #198754 !important;
}

/* Efek Hover Tombol Aksi */
.hover-primary:hover {
  background-color: #eef5ff !important;
  color: #0d6efd !important;
  border-color: #b6d4fe !important;
}
.hover-warning:hover {
  background-color: #fff8e6 !important;
  color: #d97706 !important;
  border-color: #fde68a !important;
}
.hover-danger:hover {
  background-color: #fef2f2 !important;
  color: #dc3545 !important;
  border-color: #f5c2c7 !important;
}
</style>
