<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";
import { useFilter } from "@/Composables/useFilter";
import { highlight } from "@/Helpers/Highlight";
import { formatDate } from "@/Helpers/formatDate";
import { useConfirm } from "@/Composables/useConfirm";
import { hasRole, hasPermission } from "@/Composables/useAuth";
import FormModal from "./FormModal.vue";
const confirm = useConfirm();
const props = defineProps({
  employee: Object,
  branches: Array,
  permissions: Array,
  roles: Array,
  filters: Object,
});

const page = usePage();
// Ambil ID user yang sedang login
const authUserId = computed(() => {
  return page.props.auth?.user_id ?? page.props.auth?.user?.id ?? null;
});
// Cek apakah baris tabel (item) ini adalah milik user yang sedang login
const isCurrentLoggedUser = (item) => {
  const itemId = item?.id ?? item?.id_user; // Antisipasi jika key id atau id_user
  if (!itemId || !authUserId.value) return false;

  return String(itemId) === String(authUserId.value);
};
// Helper untuk mengambil nama role dari baris data (item)
const getItemRole = (item) => {
  return (item?.role_name ?? item?.role ?? item?.roles?.[0]?.name ?? "").toLowerCase();
};

const visibleActions = (item, actions = []) => {
  if (!item || !Array.isArray(actions)) return [];
  // Jika baris ini adalah dirinya sendiri yang sedang login,
  // langsung sembunyikan SEMUA tombol aksi (return array kosong)
  if (isCurrentLoggedUser(item)) {
    return [];
  }
  const targetRole = getItemRole(item);

  //  Jika yang login adalah super_admin, sembunyikan SEMUA tombol aksi
  // saat melihat baris milik developer atau sesama super_admin
  if (hasRole(["super_admin"]) && ["developer", "super_admin"].includes(targetRole)) {
    return [];
  }
  // Jika yang login adalah supervisor, sembunyikan SEMUA tombol aksi
  // saat melihat baris milik developer, super_admin, atau sesama supervisor
  if (
    hasRole(["supervisor"]) &&
    ["developer", "super_admin", "supervisor"].includes(targetRole)
  ) {
    return [];
  }
  // Jika lolos dari 3 aturan proteksi di atas, baru saring per tombol aksi
  return actions.filter((action) => {
    // Filter berdasarkan role
    if (action.role && !hasRole(action.role)) {
      return false;
    }

    // Filter berdasarkan permission
    if (action.permission && !hasPermission(action.permission)) {
      return false;
    }

    // Filter berdasarkan kondisi show/hide (mendukung boolean atau function)
    const isShown = typeof action.show === "function" ? action.show(item) : action.show;
    if (isShown === false) {
      return false;
    }

    return true;
  });
};

const rowActions = (item) => [
  {
    title: "Ubah Pegawai",
    icon: "bi bi-pencil-square",
    class: "btn btn-c-success",
    role: ["developer", "super_admin"],
    permission: "users-edit",
    onClick: (row) => openEditModal(row),
  },
  {
    title: item.is_active ? "Nonaktifkan Akun" : "Aktifkan Akun",
    icon: item.is_active ? "bi bi-person-slash" : "bi bi-person-check-fill",
    class: item.is_active ? "btn btn-c-warning" : "btn btn-c-info",
    role: ["developer", "super_admin"],
    permission: "users-edit",
    onClick: (row) => toggleStatusUser("employee.status", row),
  },
  {
    title: "Hapus Pegawai",
    icon: "bi bi-trash",
    class: "btn btn-c-primary",
    role: ["developer", "super_admin"],
    permission: "users-delete",
    onClick: (row) => deleted("employee.delete", row),
  },
];

const showModal = ref(false);
const selectedByIdUser = ref(null);
const openEditModal = (data) => {
  showModal.value = true;
  selectedByIdUser.value = data.id;
};
const getRoleBadgeClass = (roleName) => {
  const roles = {
    developer: {
      label: "Developer",
      color: "bg-dark text-white",
    },
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
const getInitials = (name) => {
  return name
    .match(/(\b\S)?/g)
    .join("")
    .match(/(^\S|\S$)?/g)
    .join("")
    .toUpperCase();
};
const { filters, isLoading } = useFilter({
  routeName: "employee.index",
  initialFilters: {
    keyword: props.filters.keyword ?? "",
    limit: props.filters.limit ?? 10,
    status: props.filters.status ?? "all",
    branch: props.filters.branch ?? "all",
    roles: props.filters.roles ?? "all",
    sort_order: props.filters.sort_order ?? "latest",
    page: props.filters.page ?? 1,
  },
  watchFields: ["keyword", "limit", "status", "sort_order", "branch", "roles"],
  only: ["employee", "filters", "branches", "roles"],
  debounceTime: 500,
});

const header = [
  {
    label: "No",
    key: "__index",
    attrs: {
      class: "ps-4 py-3 font-medium td-number",
    },
  },
  {
    label: "INFORMASI PEGAWAI",
    key: "name",
    attrs: {
      style: {
        width: "40%",
      },
      class: "ps-4 py-3 font-medium",
    },
  },
  {
    label: "HAK AKSES / ROLE",
    key: "roles",
    attrs: {
      class: "py-3 font-medium",
    },
  },
  {
    label: "PENEMPATAN CABANG",
    key: "branch",
    attrs: {
      class: "py-3 font-medium",
    },
  },
  {
    label: "STATUS PEGAWAI",
    key: "status",
    attrs: {
      class: "py-3 font-medium",
    },
  },
  {
    label: "TGL BERGABUNG",
    key: "price",
    attrs: {
      class: "py-3 font-medium text-center",
    },
  },

  {
    label: "Aksi",
    key: "actions",
    attrs: {
      style: { width: "5%" },
      class: "pe-4 py-3 font-medium text-center",
    },
  },
];

const deleted = async (nameRoute, data) => {
  const setConfirm = await confirm.ask({
    title: `Hapus Data Pegawai ${data.name} ?`,
    message: `Kamu ingin menghapus data pegawai, data yang dihapus tidak dapat dikembalikan.`,
    confirmText: "Ya, Hapus",
    variant: "danger",
  });

  if (setConfirm) {
    // loaderActive.value?.show("Sedang menghapus data...");
    router.delete(route(nameRoute, data.id), {
      preserveScroll: false,
      replace: true,
      preserveState: true,
    });
  }
};
const toggleStatusUser = async (nameRoute, data) => {
  const setConfirm = await confirm.ask({
    title: `${data.is_active ? "Nonaktifkan" : "Aktifkan kembali"} ${data.name} ?`,
    message: `Kamu ingin ${
      data.is_active ? "menonaktifkan" : "mengaktifkan"
    } Data Pegawai, pegawai ${
      data.is_active ? "tidak dapat" : "dapat"
    } mengakses aplikasi.`,
    confirmText: "Ya, Lanjutkan",
    variant: data.is_active ? "warning" : "success",
    icon: data.is_active ? "bi bi-person-slash" : "bi bi-person-check-fill",
    variantButton: data.is_active ? "warning" : "success",
  });

  if (setConfirm) {
    // loaderActive.value?.show("Sedang menghapus data...");
    router.patch(route(nameRoute, data.id), {
      preserveScroll: true,
      replace: true,
      preserveState: true,
    });
  }
};

const branchOptions = computed(() => {
  return props.branches.map((branch) => ({
    label: branch.name,
    value: branch.name,
  }));
});

const rolesOptions = computed(() => {
  return props.roles.map((role) => ({
    label: role.name,
    value: role.name,
  }));
});

const keywordRef = ref(null);
onMounted(() => {
  keywordRef.value.focus();
});
</script>
<template>
  <Head title="Halaman Data Pegawai" />
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
                  <h5 class="fw-bold mb-1">Manajemen Pegawai</h5>
                  <small class="text-muted"
                    >Kelola akses portal dan penempatan cabang internal.</small
                  >
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
                    placeholder="Cari pegawai berdasarkan nama atau email..."
                  />
                </div>
              </div>

              <div class="col-xl-2 col-4 col-md-4 col-lg-4">
                <select-input
                  :isValid="false"
                  name="limit"
                  v-model="filters.limit"
                  text="Pilih Batas Data"
                  :options="[
                    { label: '10 Data', value: 10 },
                    { label: '25 Data', value: 25 },
                    { label: '50 Data', value: 50 },
                    { label: '100 Data', value: 100 },
                  ]"
                />
              </div>
              <div class="col-xl-2 col-4 col-md-4 col-lg-4">
                <select-input
                  :isValid="false"
                  name="sort_order"
                  v-model="filters.sort_order"
                  text="Pilih Urutan"
                  :options="[
                    { label: 'Terbaru', value: 'latest' },
                    { label: 'Terlama', value: 'oldest' },
                  ]"
                />
              </div>

              <div class="col-xl-4 col-4 col-md-4 col-lg-4">
                <select-input
                  :isValid="false"
                  name="branch"
                  v-model="filters.branch"
                  text="Pilih Cabang"
                  :options="[{ label: 'Semua Cabang', value: 'all' }, ...branchOptions]"
                />
              </div>
              <div class="col-xl-4 col-6 col-md-6 col-lg-6">
                <select-input
                  :isValid="false"
                  name="roles"
                  v-model="filters.roles"
                  text="Pilih Peran"
                  :options="[{ label: 'Semua Peran', value: 'all' }, ...rolesOptions]"
                />
              </div>
              <div class="col-xl-4 col-6 col-md-6 col-lg-6">
                <select-input
                  :isValid="false"
                  name="status"
                  v-model="filters.status"
                  text="Status Akun"
                  :options="[
                    { label: 'Semua Status Akun', value: 'all' },
                    { label: 'Aktif', value: 'active' },
                    { label: 'Tidak Aktif', value: 'inactive' },
                  ]"
                />
              </div>
            </div>

            <div class="card-body p-0">
              <table-wrapper
                :loader="isLoading"
                loaderText="Sedang memuat data..."
                :headers="header"
                :items="employee?.data"
                row-key="product_id"
              >
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
                      <div
                        class="client-avatar bg-primary-subtle text-primary rounded-circle"
                      >
                        {{ getInitials(item.name) }}
                      </div>
                      <div>
                        <h6
                          class="mb-0 fw-bold text-dark text-capitalize"
                          v-html="highlight(item.name, filters.keyword)"
                        ></h6>
                        <span class="text-muted small"  v-html="highlight(item.email, filters.keyword)" ></span>
                      </div>
                    </div>
                  </td>
                  <td class="py-3">
                    <span
                      v-if="item.role_name"
                      class="badge rounded-pill px-3 py-2 fw-medium"
                      :class="getRoleBadgeClass(item.role_name).color"
                    >
                      <i class="bi bi-shield-check me-1 opacity-75"></i>
                      {{ getRoleBadgeClass(item.role_name).label }}
                    </span>
                    <span v-else class="text-muted small"><i>Tanpa Akses</i></span>
                  </td>

                  <td class="py-3">
                    <div
                      v-if="item.branch_name"
                      class="d-flex align-items-center gap-2 text-dark fw-medium small"
                    >
                      <i class="bi bi-geo-alt-fill text-danger"></i>
                      {{ item.branch_name }}
                    </div>
                    <div
                      v-else
                      class="d-flex align-items-center gap-2 text-primary fw-bold small"
                    >
                      <i class="bi bi-building"></i> Kantor Pusat
                    </div>
                  </td>
                  <td class="text-center">
                    <div
                      style="font-size: 13px"
                      :class="`badge px-5 rounded-pill fw-medium ${
                        Boolean(item.is_active) === true
                          ? 'badge-soft-success'
                          : 'badge-soft-danger'
                      }`"
                    >
                      <i
                        :class="` ${
                          Boolean(item.is_active) === true
                            ? 'bi bi-unlock-fill'
                            : 'bi bi-lock-fill'
                        }`"
                      ></i>
                      {{ Boolean(item.is_active) === true ? "Aktif" : "Tidak Aktif" }}
                    </div>
                  </td>

                  <td class="py-3 text-center text-muted small">
                    {{ formatDate(item.created_at) }}
                  </td>
                  <td class="text-center">
                    <div
                      v-if="visibleActions(item, rowActions(item)).length > 0"
                      class="d-flex justify-content-center gap-1"
                    >
                      <button
                        v-for="(action, idx) in visibleActions(item, rowActions(item))"
                        :key="idx"
                        @click="action.onClick(item)"
                        type="button"
                        :class="action.class"
                        :title="action.title"
                      >
                        <i :class="action.icon"></i>
                      </button>
                    </div>
                    <div v-else class="d-flex justify-content-center">
                      <span
                        class="small fw-semibold badge badge-soft-light rounded-5 text-muted"
                      >
                        <i class="bi bi-lock-fill me-1"></i>No Action
                      </span>
                    </div>
                  </td>
                </template>
                <template #pagination>
                  <div v-if="employee?.data?.length > 0"
                    class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center"
                  >
                    <small class="text-muted mb-2 mb-md-0">
                      Menampilkan <strong>{{ employee.from ?? 0 }}</strong> -
                      <strong>{{ employee.to ?? 0 }}</strong> dari
                      <strong>{{ employee.total ?? 0 }}</strong>
                    </small>
                    <pagination
                      size="pagination-sm"
                      :links="employee?.links"
                      routeName="employee.index"
                      :additionalQuery="{
                        keyword: filters.keyword,
                        status: filters.status,
                        limit: filters.limit,
                        sort_order: filters.sort_order,
                        branch: filters.branch,
                        roles: filters.roles,
                      }"
                      :only="['employee', 'filters', 'branches', 'roles']"
                    />
                  </div>
                </template>
              </table-wrapper>
            </div>
          </div>
        </div>
      </div>
      <FormModal
        :permissions="props.permissions"
        :idUser="selectedByIdUser"
        :branches="props.branches"
        :show="showModal"
        @update:show="showModal = $event"
      />
    </template>
  </app-layout>
</template>
