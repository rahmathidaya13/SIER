<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";
import { useFilter } from "@/Composables/useFilter";
import { useConfirm } from "@/Composables/useConfirm";
import { highlight } from "@/Helpers/Highlight";
import { formatDate } from "@/Helpers/formatDate";
import { hasRole, hasPermission, canAccess } from "@/Composables/useAuth";
import FormModal from "./FormModal.vue";
import ConvertLeadModal from "./ConvertLeadModal.vue";
import ActivityTimelineModal from "./ActivityTimelineModal.vue";
import FormActivityFollUp from "./FormActivityFollUp.vue";
const confirm = useConfirm();

const props = defineProps({
  leads: Object,
  filters: Object,
  channels: Array,
  role_name: String,
});
console.log(props.leads.data);
const getCurrentMonth = () => {
  const today = new Date();
  const year = today.getFullYear();

  const month = String(today.getMonth() + 1).padStart(2, "0");
  return `${year}-${month}`;
};
const { filters, isLoading } = useFilter({
  routeName: "leads.index",
  initialFilters: {
    keyword: props.filters.keyword ?? "",
    limit: props.filters.limit ?? 10,
    status: props.filters.status ?? "active",
    periode: props.filters.periode ?? getCurrentMonth(),
    order_by: props.filters.order_by ?? "latest",
    page: props.filters.page ?? 1,
  },
  watchFields: ["keyword", "limit", "periode", "status", "order_by"],
  only: ["leads", "filters", "channels"],
  debounceTime: 500,
});
const header = [
  {
    label: "No",
    key: "__index",
    attrs: {
      class: "text-center td-number",
    },
  },
  {
    label: "PROSPEK & KONTAK",
    key: "prospect",
  },
  {
    label: "SUMBER & PRODUK",
    key: "source",
  },
  {
    label: "SUHU",
    key: "temperature",
    attrs: {
      class: "text-center",
    },
  },
  {
    label: "STATUS",
    key: "status_",
    attrs: {
      class: "text-center",
    },
  },
  {
    label: "Aktivitas FOLL-UP",
    key: "price",
    attrs: {
      class: "text-center",
    },
  },
  {
    label: "Dibuat Oleh",
    key: "creator",
    attrs: {
      class: "text-center",
    },
  },

  {
    label: "Aksi",
    key: "actions",
    attrs: {
      class: "text-center",
    },
  },
];

const showModal = ref(false);
const showConvertModal = ref(false);
const showFormFollUp = ref(false);
const selectedId = ref(null);

const openCreateModal = () => {
  showModal.value = true;
  selectedId.value = null;
};
const openConvertModal = (data) => {
  showConvertModal.value = true;
  selectedId.value = data.id_lead;
};
const openEditModal = async (data) => {
  showModal.value = true;
  selectedId.value = data.id_lead;
};
const openFollowUpModal = (data) => {
  showFormFollUp.value = true;
  selectedId.value = data.id_lead;
};
const handleLeadSaved = (payload) => {
  // Jika data yang baru disimpan (baik baru/edit) memiliki status 'CLOSED_WON' (Closing)
  if (
    payload.status === "CLOSED_WON" &&
    payload.temperature === "HOT" &&
    payload.id_lead
  ) {
    // Beri jeda 300ms agar animasi Modal lama menutup dulu dengan mulus, baru buka modal baru
    setTimeout(() => {
      selectedId.value = payload.id_lead; // Suntikkan ID baru ke state
      showConvertModal.value = true; // BUM! Modal Closing otomatis mekar!
    }, 300);
  }
};
const keywordRef = ref(null);
onMounted(() => {
  keywordRef.value.focus();
});

const isOverdue = (dateString, status) => {
  if (!dateString || status === "CLOSED_WON" || status === "CLOSED_LOST") return false;
  const today = new Date().setHours(0, 0, 0, 0);
  const follDate = new Date(dateString).setHours(0, 0, 0, 0);
  return follDate <= today;
};
const getWaLink = (phone) => {
  let p = phone.replace(/\D/g, ""); // Hapus spasi, strip, tanda plus
  if (p.startsWith("0")) p = "62" + p.slice(1);
  return `https://wa.me/${p}`;
};
const getEmail = (email) => {
  return `mailto:${email}`;
};
const getTempBadge = (temp) => {
  const map = {
    HOT: {
      class: "badge-soft-success border border-success",
      label: "🔥 HOT",
      title: "Suhu Hot menandakan bahwa prospek sudah siap bayar",
    },
    WARM: {
      class: "badge-soft-warning border border-warning",
      label: "⏳ WARM",
      title: "Suhu Warm menandakan bahwa pelanggan bertanya-tanya tentang detail produk",
    },
    COLD: {
      class: "badge-soft-info border border-info",
      label: "❄️ COLD",
      title: "Suhu Cold menandakan bahwa pelanggan hanya sekedar ingin kepoin produk",
    },
  };
  return map[temp] || { class: "badge-soft-dark", label: temp, title: temp };
};
const getStatusBadge = (status) => {
  const map = {
    NEW: {
      class: "badge-soft-primary border border-primary",
      label: "⭐ Baru Masuk",
    },
    FOLLOW_UP: {
      class: "badge-soft-info border border-info",
      label: "💬 Follow Up",
    },
    CLOSED_WON: {
      class: "badge-soft-success border border-success",
      label: "✔ CLOSING (Terjual)",
    },
    CLOSED_LOST: {
      class: "badge-soft-danger border border-danger",
      label: "✖ Batal / Lost",
    },
  };
  return map[status] || { class: "badge-soft-dark", label: status };
};
const rowClasses = (item) => ({
  "tr-highlight-primary": item.status === "NEW",
  "tr-dimmed": item.status === "CLOSED_LOST",
  // 'tr-highlight-success': item.status === 'CLOSED_WON',
  "tr-highlight-warning": isOverdue(item.follow_up_date, item.status),
});

const deleted = async (nameRoute, data) => {
  const setConfirm = await confirm.ask({
    title: "Hapus",
    message: `Kamu ingin menghapus Data Lead dari ${data.name} ? Data yang sudah dihapus tidak dapat dikembalikan!`,
    confirmText: "Ya, Hapus",
    variant: "danger",
  });

  if (setConfirm) {
    router.delete(route(nameRoute, data.id_lead), {
      preserveScroll: true,
      replace: true,
      preserveState: true,
    });
  }
};
const showHistoryModal = ref(false);
const openHistoryModal = (data) => {
  selectedId.value = data.id_lead;
  showHistoryModal.value = true;
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

const customerTagMeta = (tag) => {
  const map = {
    new: {
      label: "Pelanggan Baru",
      icon: "bi-sparkles",
      class: "bg-info bg-opacity-10 text-dark border-info-subtle",
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
// const canAccess = (permission, role = []) => {
//   return hasRole(role) || hasPermission(permission);
// };

const page = usePage();
const authUserId = computed(() => String(page.props.auth?.user_id ?? ""));
const isOwner = (item) => String(item?.created_by) === authUserId.value;
const isSupervisorOrAbove = computed(() =>
  hasRole(["supervisor", "super_admin", "developer"])
);
const actionLabel = (item) => {
  // Tentukan base label dari status
  const base = item.status === "NEW" ? " Follow Up" : "Catat Aktivitas";
  // Kalau pemilik lead → tanpa prefix
  if (isOwner(item)) return base;

  // Kalau supervisor ke atas → tambah "Bantu"
  if (isSupervisorOrAbove.value) return `Bantu ${base}`;
  // Fallback (misal sales lain) — tampilkan base saja, tombol mungkin disembunyikan
  return base;
};
</script>
<template>
  <Head title="Data Rekap Leads" />
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
                  <h5 class="fw-bold mb-1">Data Prospek (Leads)</h5>
                  <small class="text-muted"
                    >Prospek yang belum melakukan pembayaran deal</small
                  >
                </div>
                <button
                  v-if="canAccess('lead-create')"
                  @click.prevent="openCreateModal"
                  class="btn btn-c-primary px-4 py-2"
                >
                  <i class="bi bi-plus-circle me-1"></i> Lead Baru
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
                    placeholder="Cari nama pelanggan...."
                  />
                </div>
              </div>
              <div class="col-xl-2 col-3 col-md-3 col-lg-3">
                <input-text
                  :isValid="false"
                  type="month"
                  name="periode"
                  v-model="filters.periode"
                />
              </div>
              <div class="col-xl-2 col-3 col-md-3 col-lg-3">
                <select-input
                  :isValid="false"
                  name="status"
                  v-model="filters.status"
                  text="Status Prospek"
                  :options="[
                    { label: '📋 Semua Riwayat Data', value: 'all' },
                    {
                      label: '🔥 Prospek Aktif (Baru Masuk & Follow Up)',
                      value: 'active',
                    },
                    { label: '🆕 Hanya Baru Masuk', value: 'new' },
                    { label: '📞 Hanya Follow Up', value: 'follow_up' },
                    { label: '🎉 Closing / Terjual', value: 'closed_won' },
                    { label: '❌ Tidak Jadi', value: 'closed_lost' },
                  ]"
                />
              </div>
              <div class="col-xl-1 col-3 col-md-3 col-lg-3">
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
              <div class="col-xl-1 col-3 col-md-3 col-lg-3">
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
                :items="leads?.data"
                rowKey="id_lead"
              >
                <template #empty>
                  <div class="text-center py-5">
                    <i class="bi bi-funnel fa-3x text-muted opacity-25 mb-3 d-block"></i>
                    <h6 class="text-muted fw-bold">Data leads Anda Masih Kosong</h6>
                    <p class="text-muted small mb-0">
                      Klik tombol "Tambah Lead Baru" di pojok kanan atas untuk memasukkan
                      lead pertama.
                    </p>
                  </div>
                </template>

                <template #row="{ item, index }">
                  <td class="td-number">
                    {{ index + 1 + (leads?.current_page - 1) * leads?.per_page }}
                  </td>

                  <!-- 2. Info Pelanggan & Tag -->
                  <td class="align-middle py-2">
                    <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                      <div
                        @click.prevent="openHistoryModal(item)"
                        class="fw-bold text-primary text-decoration-none cursor-pointer text-capitalize"
                        v-html="highlight(item.customer_name, filters.keyword)"
                      ></div>

                      <!-- Badge Customer Tag (new, repeat, dll) -->
                      <span
                        v-if="item.customer_tag"
                        class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 fw-semibold text-capitalize"
                        style="font-size: 12px"
                      >
                        {{
                          typeof customerTagMeta === "function"
                            ? customerTagMeta(item.customer_tag).label
                            : item.customer_tag
                        }}
                      </span>
                    </div>

                    <!-- Email Pelanggan -->
                    <div
                      v-if="item.customer_email"
                      class="text-muted mb-1 d-flex align-items-center"
                      style="font-size: 12px"
                    >
                      <i class="bi bi-envelope me-1 opacity-75"></i>
                      <a
                        :href="getEmail(item.customer_email)"
                        class="text-muted text-decoration-none text-truncate"
                        style="max-width: 210px"
                        :title="item.customer_email"
                      >
                        {{ item.customer_email }}
                      </a>
                    </div>

                    <!-- Tombol WA & Indikator Catatan -->
                    <div class="d-flex align-items-center flex-wrap gap-1">
                      <a
                        :href="getWaLink(item.customer_phone)"
                        target="_blank"
                        class="badge badge-soft-success text-success rounded-pill d-inline-flex align-items-center gap-1 py-1 px-2 text-decoration-none"
                        title="Chat langsung via WhatsApp"
                      >
                        <i class="bi bi-whatsapp"></i> {{ item.customer_phone }}
                      </a>

                      <!-- Indikator Catatan jika ada notes -->
                      <span
                        v-if="item.notes"
                        class="badge cursor-pointer bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill py-1 px-2"
                        style="font-size: 11px"
                        :title="item.notes"
                      >
                        <i class="bi bi-journal-text"></i> Catatan
                      </span>
                    </div>
                  </td>

                  <td class="align-middle py-2">
                    <div class="d-flex flex-column gap-2 py-1">
                      <div class="d-inline-flex align-items-center">
                        <span
                          class="badge bg-white border shadow-sm text-dark d-flex align-items-center py-1 px-3 rounded-pill"
                          style="font-size: 12.5px"
                        >
                          <!-- Ikon Channel -->
                          <div
                            class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-2"
                            style="width: 22px; height: 22px"
                          >
                            <i
                              :class="item.icon || 'bi-broadcast'"
                              style="font-size: 11px"
                            ></i>
                          </div>
                          <span
                            class="fw-semibold text-uppercase"
                            style="letter-spacing: 0.3px"
                          >
                            {{ item.channel_name || "Sumber Tidak Diketahui" }}
                          </span>
                        </span>
                      </div>

                      <div
                        v-if="item.lead_product && item.lead_product.length > 0"
                        class="d-flex flex-wrap gap-2 mt-1 ms-1"
                      >
                        <template
                          v-for="(lp, index) in item.lead_product.slice(0, 2)"
                          :key="lp.product?.id_product || index"
                        >
                          <span
                            v-if="lp.product"
                            class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-flex align-items-center py-1 px-3 rounded-pill shadow-none"
                            style="font-size: 11.5px; max-width: 300px"
                            :title="lp.product.name"
                          >
                            <i class="bi bi-box-seam me-2 opacity-75"></i>
                            <span class="text-truncate fw-medium">{{
                              lp.product.name
                            }}</span>
                          </span>
                        </template>

                        <span
                          v-if="item.lead_product.length > 2"
                          class="badge bg-light text-secondary border d-flex align-items-center py-1 px-2 rounded-pill shadow-none cursor-pointer"
                          style="font-size: 11px"
                          :title="
                            item.lead_product
                              .slice(2)
                              .map((p) => p.product?.name)
                              .join('\n')
                          "
                        >
                          <span class="fw-bold me-1"
                            >+{{ item.lead_product.length - 2 }}</span
                          >
                          Lainnya
                        </span>
                      </div>

                      <div v-else class="ms-1">
                        <span
                          class="text-muted fst-italic d-flex align-items-center"
                          style="font-size: 11.5px"
                        >
                          <i class="bi bi-dash-circle me-1 opacity-50"></i> Belum ada
                          produk
                        </span>
                      </div>
                    </div>
                  </td>

                  <td class="text-center">
                    <span
                      class="badge rounded-pill px-3 py-1 fw-bold"
                      :title="getTempBadge(item.temperature).title"
                      :class="getTempBadge(item.temperature).class"
                    >
                      {{ getTempBadge(item.temperature).label }}
                    </span>
                  </td>

                  <td class="text-center align-middle">
                    <div class="d-flex flex-column align-items-center gap-1">
                      <span
                        class="badge rounded-pill px-3 py-1 border-1"
                        :title="getStatusBadge(item.status).label"
                        :class="getStatusBadge(item.status).class"
                      >
                        {{ getStatusBadge(item.status).label }}
                      </span>

                      <!-- Jika sudah ada nomor invoice -->
                      <span
                        v-if="item.invoice"
                        class="badge bg-light text-dark border font-monospace px-2 py-1 rounded-2"
                        style="font-size: 11px"
                      >
                        <i class="bi bi-receipt text-primary me-1"></i>{{ item.invoice }}
                      </span>
                    </div>
                  </td>

                  <td class="text-start align-middle py-2" style="min-width: 185px">
                    <div
                      v-if="item.follow_up_date"
                      class="p-2 bg-light rounded-3 border border-secondary border-opacity-10 mb-2"
                    >
                      <!-- Tanggal Mulai Follow-Up -->
                      <div
                        class="d-flex align-items-center justify-content-between text-muted mb-1"
                        style="font-size: 11px"
                      >
                        <span class="d-flex align-items-center">
                          <i class="bi bi-play-circle-fill text-primary me-1"></i> Mulai:
                        </span>
                        <span class="fw-medium text-dark">{{
                          formatDate(item.follow_up_date)
                        }}</span>
                      </div>

                      <!-- Aktivitas Terakhir & Counter Jumlah Log -->
                      <div
                        v-if="item.activity && item.activity.length > 0"
                        class="d-flex align-items-center justify-content-between text-muted pt-1 border-top border-secondary border-opacity-10"
                        style="font-size: 12px"
                      >
                        <span class="d-flex align-items-center me-1">
                          <i class="bi bi-clock-history text-success me-1"></i> Terakhir:
                        </span>
                        <div class="d-flex align-items-center gap-1">
                          <span class="fw-bold text-dark">{{
                            formatDate(item.activity[0].created_at)
                          }}</span>
                          <span
                            class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-1 py-0"
                            style="font-size: 12px"
                            :title="`Total ${item.activity.length} riwayat aktivitas`"
                          >
                            {{ item.activity.length }}x
                          </span>
                        </div>
                      </div>
                    </div>

                    <div
                      v-else
                      class="text-center mb-2 px-2 py-2 bg-warning-subtle rounded-2 border border-warning-subtle"
                    >
                      <span class="text-warning-emphasis fw-bold" style="font-size: 12px">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Belum
                        direspon!
                      </span>
                    </div>

                    <div
                      v-if="item.status === 'FOLLOW_UP' || item.status === 'NEW'"
                      class="d-grid"
                    >
                      <button
                        @click.prevent="openFollowUpModal(item)"
                        type="button"
                        class="btn btn-sm shadow-sm fw-medium d-flex justify-content-center align-items-center gap-1 py-1"
                        :class="
                          item.status === 'NEW' ? 'btn-primary' : 'btn-outline-primary'
                        "
                        style="font-size: 14px"
                      >
                        <i class="bi bi-chat-dots-fill"></i>
                        {{ actionLabel(item) }}
                      </button>
                    </div>
                  </td>

                  <td class="align-middle text-start py-2" style="min-width: 210px">
                    <div class="d-flex align-items-start">
                      <!-- Avatar Inisial -->
                      <div
                        class="client-avatar me-2 bg-gradient-light border text-primary fw-bold small d-flex align-items-center justify-content-center shadow-sm flex-shrink-0 mt-1"
                      >
                        {{
                          item.creator_name
                            ? item.creator_name.substring(0, 2).toUpperCase()
                            : "??"
                        }}
                      </div>

                      <div class="d-flex flex-column small" style="line-height: 1.35">
                        <!-- Nama Creator & Role -->
                        <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                          <span class="fw-bold text-dark text-capitalize">
                            {{ item.creator_name || "Sistem" }}
                          </span>
                          <span
                            v-if="item.creator_role_name"
                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded-pill px-2 py-1 fw-medium"
                            style="font-size: 12px"
                          >
                            {{ getRoleName(item.creator_role_name).label }}
                          </span>
                        </div>

                        <!-- Nama Cabang -->
                        <span
                          class="text-secondary fw-semibold mb-1"
                          style="font-size: 12px"
                        >
                          <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                          {{ item.branch_name || "-" }}
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

                          <!-- Tampil jika pernah diupdate -->
                          <span
                            v-if="item.created_at !== item.updated_at"
                            class="text-muted"
                          >
                            <i class="bi bi-pencil-square me-1 text-warning"></i>
                            {{ formatDate(item.updated_at) }}
                            <!-- Jika diedit oleh orang yang berbeda dari pembuat awal -->
                            <template
                              v-if="item.updated_by && item.updated_by !== item.created_by"
                            >
                              oleh
                              <strong class="text-dark text-capitalize">{{
                                item.updater_name
                              }}</strong>
                              <span v-if="item.updater_role_name" class="opacity-75">
                                ({{ getRoleName(item.updater_role_name).label }})
                              </span>
                            </template>
                          </span>

                        </div>
                      </div>
                    </div>
                  </td>

                  <td class="text-center">
                    <div
                      v-if="
                        (canAccess('lead-edit', item, { strictOwner: true }) ||
                          canAccess('lead-convert', item, { strictOwner: true }) ||
                          canAccess('lead-delete', item, { strictOwner: true })) &&
                        item.status !== 'CLOSED_LOST'
                      "
                      class="d-flex justify-content-center gap-1"
                    >
                      <template
                        v-if="
                          (canAccess('lead-edit', item, { strictOwner: true }) ||
                            canAccess('lead-convert', item, { strictOwner: true })) &&
                          item.status !== 'CLOSED_WON'
                        "
                      >
                        <button
                          v-if="
                            canAccess('lead-edit', item, { strictOwner: true }) &&
                            item.status !== 'CLOSED_WON'
                          "
                          @click="openEditModal(item)"
                          :class="`btn btn-c-success ${
                            item.status === 'CLOSED_WON' ? 'disabled' : ''
                          }`"
                          title="Edit Data"
                        >
                          <i class="bi bi-pencil-square"></i>
                        </button>

                        <button
                          v-if="
                            canAccess('lead-convert', item, { strictOwner: true }) &&
                            item.status !== 'CLOSED_WON'
                          "
                          @click="openConvertModal(item)"
                          class="btn btn-c-info"
                          title="Sahkan Closing"
                        >
                          <i class="bi bi-rocket-takeoff-fill"></i>
                        </button>
                      </template>

                      <template v-else>
                        <Link
                          :href="route('sales.index', { keyword: item.invoice })"
                          class="btn btn-c-primary"
                          title="Lihat Invoice"
                        >
                          <i class="bi bi-receipt"></i>
                        </Link>
                      </template>

                      <button
                        v-if="
                          canAccess('lead-delete', item, { strictOwner: true }) &&
                          item.status !== 'CLOSED_WON'
                        "
                        @click="deleted('leads.delete', item)"
                        class="btn btn-c-primary"
                        title="Hapus Lead"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                    <div v-else class="d-flex justify-content-center gap-1">
                      <span class="small fw-semibold badge badge-soft-info rounded-5"
                        >No Action
                      </span>
                    </div>
                  </td>
                </template>

                <template #pagination>
                  <div
                    v-if="leads?.data.length > 0"
                    class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center"
                  >
                    <small class="text-muted mb-2 mb-md-0">
                      Menampilkan <strong>{{ leads.from ?? 0 }}</strong> -
                      <strong>{{ leads.to ?? 0 }}</strong> dari
                      <strong>{{ leads.total ?? 0 }}</strong>
                    </small>

                    <pagination
                      size="pagination-sm"
                      :links="leads?.links"
                      routeName="leads.index"
                      :additionalQuery="{
                        keyword: filters.keyword,
                        status: filters.status,
                        branch: filters.branch,
                        periode: filters.periode,
                        limit: filters.limit,
                        order_by: filters.order_by,
                      }"
                      :only="['leads', 'filters', 'channels']"
                    />
                  </div>
                </template>
              </table-wrapper>
            </div>
          </div>
        </div>
        <FormModal
          @saved="handleLeadSaved"
          :channels="props.channels"
          :idLead="selectedId"
          :show="showModal"
          @update:show="showModal = $event"
        />
        <ConvertLeadModal
          :idLead="selectedId"
          :show="showConvertModal"
          @update:show="showConvertModal = $event"
        />

        <ActivityTimelineModal
          :idLead="selectedId"
          :show="showHistoryModal"
          @update:show="showHistoryModal = $event"
        />

        <FormActivityFollUp
          :idLead="selectedId"
          :show="showFormFollUp"
          @update:show="showFormFollUp = $event"
        />
      </div>
    </template>
  </app-layout>
</template>
