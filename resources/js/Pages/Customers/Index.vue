<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed, h } from "vue";
import { useFilter } from "@/Composables/useFilter";
import { formatDate } from "@/Helpers/formatDate";
import FormModal from "./FormModal.vue";
import { useConfirm } from "@/Composables/useConfirm";
import { highlight } from "@/Helpers/Highlight";
import { hasRole } from "@/Composables/useAuth";

const confirm = useConfirm();
const props = defineProps({
  customers: Object,
  filters: Object,
  branches: Array,
});

const page = usePage();
const canSelectBranch = computed(() => hasRole(["super_admin", "developer"]));
const branchName = computed(() => {
  const user = page.props.auth?.user;
  return (
    user?.branch?.name ||
    props.branches?.find((b) => b.id_branch === user?.id_branch)?.name ||
    "Cabang Tidak Diketahui"
  );
});
console.log(page.props.auth?.user);
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
    label: "Kontak & NPWP",
    key: "contact",
    attrs: {
      class: "font-medium",
    },
  },
  {
    label: "Klasifikasi",
    key: "bussiness_type",
    attrs: {
      class: "font-medium",
    },
  },
  {
    label: "Lokasi & Alamat",
    key: "address",
    attrs: {
      class: "font-medium",
    },
  },
  {
    label: "Status & Riwayat",
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
    branch: !canSelectBranch.value ? props.filters.branch ?? branchName.value : null,
  },
  watchFields: ["keyword", "limit", "order_by", "branch"],
  only: ["customers", "filters"],
  debounceTime: 500,
});

// Helper untuk mengambil maksimal 2 huruf inisial nama
const getInitials = (name) => {
  if (!name) return "?";
  return name
    .split(" ")
    .filter(Boolean)
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
const customerTagMeta = (tag) => {
  const map = {
    new: {
      label: "Pelanggan Baru",
      icon: "bi-sparkles",
      class: "bg-info bg-opacity-10 text-info-emphasis border-info-subtle",
    },
    repeat: {
      label: "Pelanggan Lama",
      icon: "bi-arrow-repeat",
      class: "bg-primary bg-opacity-10 text-primary border-primary-subtle",
    },
    has_cancelled: {
      label: "Pernah Cancel",
      icon: "bi-exclamation-triangle-fill",
      class: "bg-warning bg-opacity-15 text-warning-emphasis border-warning-subtle",
    },
  };
  return map[tag] || map.new;
};

const statusMeta = (status) => {
  const map = {
    active: {
      label: "Aktif",
      icon: "bi-check-circle-fill",
      class: "bg-success bg-opacity-10 text-success border-success-subtle",
    },
    inactive: {
      label: "Nonaktif",
      icon: "bi-pause-circle-fill",
      class: "bg-secondary bg-opacity-10 text-secondary border-secondary-subtle",
    },
    blacklisted: {
      label: "Blacklist",
      icon: "bi-slash-circle-fill",
      class: "bg-danger text-white border-danger shadow-sm",
    },
  };
  return map[status] || map.active;
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

const deleted = async (nameRoute, data) => {
  const setConfirm = await confirm.ask({
    title: "Hapus",
    message: `Kamu ingin menghapus Data Pelanggan ${data.name} ?`,
    confirmText: "Ya, Hapus",
    variant: "danger",
  });

  if (setConfirm) {
    isLoading.value = true;
    router.delete(route(nameRoute, data.id_customer), {
      preserveScroll: false,
      replace: true,
      preserveState: true,
      onFinish: () => {
        isLoading.value = false;
      },
    });
  }
};
const branchOptions = computed(() => {
  const branches = props.branches ?? [];
  // Super Admin & Developer: tampilkan semua cabang
  if (canSelectBranch.value) {
    return branches.map((data) => ({
      label: data.name,
      value: data.name,
    }));
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
const isOwner = (val) => {
  console.log(val);

  if (!val) return false;

  // 1. Super Admin, Developer, dan Supervisor bebas edit & hapus
  if (hasRole(["super_admin", "developer", "supervisor"])) {
    return true;
  }

  // 2. Ambil ID user yang sedang login
  const currentUserId = page.props.auth?.user_id || page.props.auth?.user?.id;

  // 3. Ambil ID pembuat data (mendukung format val.creator, val.created_by.id, atau val.created_by)
  const creatorId =  val.creator_id;
  // 4. Role di bawah SPV HANYA boleh edit/hapus jika dia adalah pembuat datanya sendiri
  return Boolean(currentUserId && creatorId && String(creatorId) === String(currentUserId));
};
// console.log(props.customers);
</script>
<template>
  <Head title="Halaman Pelanggan" />
  <app-layout>
    <template #content>
      <div class="row g-3 view-section">
        <div class="col-12">
          <toast :duration="10" />

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

            <div class="row p-3 g-2 align-items-center">
              <div class="col-12 col-md-12 col-lg-12 col-xl-6">
                <div class="position-relative">
                  <i class="bi bi-search input-icon-left"></i>
                  <input-text
                    ref="keywordRef"
                    :isValid="false"
                    v-model="filters.keyword"
                    inputClass="input-fixed-height input-height-1 rounded-pill"
                    placeholder="Cari nama pelanggan & No.Handphone ...."
                  />
                </div>
              </div>
              <div class="col-xl-2 col-4 col-md-4 col-lg-4">
                <select-input
                  :disabled="!canSelectBranch"
                  :isValid="false"
                  name="branch_selected"
                  v-model="filters.branch"
                  text="Pilih pelanggan cabang
                  "
                  :options="branchOptions"
                />
              </div>
              <div class="col-xl-2 col-4 col-md-4 col-lg-4">
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
              <div class="col-xl-2 col-4 col-md-4 col-lg-4">
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
                  <td class="align-middle" style="min-width: 220px">
                    <div class="d-flex align-items-center gap-2">
                      <!-- Avatar dengan Indikator Bintang Member -->
                      <div class="position-relative flex-shrink-0">
                        <div
                          class="avatar-circle fw-bold rounded-circle d-flex align-items-center justify-content-center border"
                          :class="
                            item.status === 'blacklisted'
                              ? 'bg-danger bg-opacity-10 text-danger border-danger-subtle'
                              : item.is_member
                              ? 'bg-warning bg-opacity-15 text-warning-emphasis border-warning'
                              : 'bg-primary bg-opacity-10 text-primary border-primary-subtle'
                          "
                        >
                          {{ getInitials(item.name) }}
                        </div>
                        <span
                          v-if="item.is_member"
                          class="member-star-badge bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center shadow-sm border border-white"
                          title="Pelanggan Member"
                        >
                          <i class="bi bi-star-fill" style="font-size: 8px"></i>
                        </span>
                      </div>

                      <div class="min-w-0">
                        <div class="d-flex align-items-center gap-2">
                          <span
                            class="fw-bold text-dark text-capitalize text-truncate"
                            style="max-width: 170px; font-size: 14px"
                            :title="item.name"
                            v-html="highlight(item.name, filters.keyword)"
                          >
                          </span>
                        </div>

                        <!-- Baris Kode Pelanggan & Badge Member -->
                        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                          <span class="text-muted font-monospace" style="font-size: 12px">
                            {{
                              item.customer_code ||
                              item.id_customer?.substring(0, 8).toUpperCase()
                            }}
                          </span>
                          <span
                            v-if="item.is_member"
                            class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning-subtle rounded-pill px-1 py-0"
                            style="font-size: 10px"
                          >
                            Member
                          </span>
                        </div>

                        <!-- Baris PIC (Muncul jika ada nama PIC) -->
                        <div
                          v-if="item.pic_name"
                          class="text-muted d-flex align-items-center gap-1 mt-1"
                          style="font-size: 11px"
                        >
                          <i class="bi bi-person-badge text-secondary"></i>
                          <span
                            class="text-truncate"
                            style="max-width: 150px"
                            :title="`PIC: ${item.pic_name}`"
                          >
                            PIC:
                            <strong class="text-dark fw-medium text-capitalize">{{
                              item.pic_name
                            }}</strong>
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Kontak (Telepon & Email) -->
                  <td class="align-middle" style="min-width: 170px">
                    <div class="d-flex flex-column gap-1">
                      <!-- Nomor Telepon / WhatsApp -->
                      <a
                        :href="`https://wa.me/${item.phone?.replace(/^0/, '62')}`"
                        target="_blank"
                        class="text-decoration-none text-dark fw-semibold d-inline-flex align-items-center gap-2 hover-whatsapp"
                        style="font-size: 14px"
                        title="Hubungi via WhatsApp"
                      >
                        <i class="bi bi-whatsapp text-success"></i>
                        <span v-html="highlight(item.phone, filters.keyword)"></span>
                      </a>

                      <!-- Email -->
                      <div
                        v-if="item.email"
                        class="text-muted d-flex align-items-center gap-2"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-envelope text-secondary"></i>
                        <span
                          class="text-truncate"
                          style="max-width: 155px"
                          :title="item.email"
                        >
                          {{ item.email }}
                        </span>
                      </div>
                      <span v-else class="text-muted fst-italic" style="font-size: 12px">
                        Tanpa email
                      </span>

                      <!-- NPWP (Muncul jika ada tax_id) -->
                      <div
                        v-if="item.tax_number"
                        class="d-inline-flex align-items-center gap-1 text-secondary bg-light border rounded px-2 py-0 align-self-start mt-1"
                        style="font-size: 12px"
                        :title="`NPWP: ${item.tax_number}`"
                      >
                        <i class="bi bi-card-heading"></i>
                        <span class="text-truncate" style="max-width: 130px">{{
                          item.tax_number
                        }}</span>
                      </div>
                    </div>
                  </td>

                  <!-- 4. Klasifikasi (Tipe Pelanggan & Sektor Bisnis) -->
                  <td class="align-middle">
                    <div class="d-flex flex-column align-items-start gap-2">
                      <span
                        class="badge border rounded-pill fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1"
                        :class="customerTypeMeta(item.customer_type).class"
                        style="font-size: 12px"
                      >
                        <i
                          class="bi"
                          :class="customerTypeMeta(item.customer_type).icon"
                        ></i>
                        {{ customerTypeMeta(item.customer_type).label }}
                      </span>

                      <span
                        class="text-muted d-inline-flex align-items-center gap-2"
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

                  <!-- 5. Lokasi & Cabang (Kota + Alamat + Cabang) -->
                  <td class="align-middle" style="max-width: 230px; min-width: 180px">
                    <div class="d-flex flex-column gap-1">
                      <!-- Kota & Cabang -->
                      <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span
                          v-if="item.city"
                          class="badge bg-light text-dark border fw-semibold px-2 py-1 text-capitalize"
                          style="font-size: 11px"
                        >
                          <i class="bi bi-pin-map-fill text-primary me-1"></i
                          >{{ item.city }}
                        </span>
                        <span
                          class="text-muted d-inline-flex align-items-center gap-2"
                          style="font-size: 12px"
                          title="Cabang Terdaftar"
                        >
                          <i class="bi bi-geo-alt-fill text-danger opacity-75"></i>
                          <span class="fw-medium">{{
                            item.branch_name || item.branch?.name || "Pusat"
                          }}</span>
                        </span>
                      </div>

                      <!-- Alamat Lengkap (Maksimal 2 Baris) -->
                      <p
                        class="mb-0 text-muted line-clamp-2"
                        style="font-size: 12px; line-height: 1.35"
                        :title="item.address"
                      >
                        {{ item.address || "-" }}
                      </p>
                    </div>
                  </td>

                  <!-- 6. Info Pencatatan (Created At & Created By) -->
                  <!-- 6. Status Operasional, Riwayat (Customer Tag) & Info Pencatatan -->
                  <td class="align-middle">
                    <div class="d-flex flex-column align-items-start gap-1">
                      <!-- Baris Badge: Status & Customer Tag -->
                      <div class="d-flex align-items-center gap-1 flex-wrap">
                        <!-- Badge Status (Aktif / Nonaktif / Blacklist) -->
                        <span
                          class="badge border rounded-pill fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1"
                          :class="statusMeta(item.status).class"
                          style="font-size: 10px"
                        >
                          <i class="bi" :class="statusMeta(item.status).icon"></i>
                          {{ statusMeta(item.status).label }}
                        </span>

                        <!-- Badge Customer Tag (Pelanggan Baru / Lama / Pernah Cancel) -->
                        <span
                          class="badge border rounded-pill fw-semibold px-2 py-1 d-inline-flex align-items-center gap-1"
                          :class="customerTagMeta(item.customer_tag).class"
                          style="font-size: 10px"
                        >
                          <i
                            class="bi"
                            :class="customerTagMeta(item.customer_tag).icon"
                          ></i>
                          {{ customerTagMeta(item.customer_tag).label }}
                        </span>
                      </div>

                      <!-- Tanggal & Pembuat -->
                      <div
                        class="d-flex align-items-start gap-2 flex-column text-muted mt-1"
                        style="font-size: 12px"
                      >
                        <span class="text-dark fw-medium">{{
                          formatDate(item.created_at)
                        }}</span>
                        <span
                          class="text-truncate text-capitalize"
                          :title="item.creator_name || item.creator?.name"
                          style="font-size: 12px"
                        >
                          {{ `(${item.creator_name} - ${item.branch_name})` }}
                        </span>
                      </div>
                    </div>
                  </td>

                  <td class="text-center">
                    <div v-if="isOwner(item)" class="d-flex gap-1 justify-content-center">
                      <button
                        @click.prevent="openEditModal(item)"
                        type="button"
                        class="btn btn-c-slate"
                        title="Edit"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        @click.prevent="deleted('customer.delete', item)"
                        type="button"
                        class="btn btn-c-danger"
                        title="Hapus"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                    <div v-else>
                      <span class="badge badge-soft-info rounded-3 py-1">
                        <i class="bi bi-lock"></i>
                        Tidak Ada Aksi
                        </span>
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
                        status: filters.status,
                        branch: filters.branch,
                        periode: filters.periode,
                        limit: filters.limit,
                        order_by: filters.order_by,
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
        :branches="props.branches"
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
.member-star-badge {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 16px;
  height: 16px;
}
.cursor-help {
  cursor: help;
}
/* Memotong teks alamat maksimal 2 baris */
.line-clamp-2 {
  display: -webkit-box;
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
