<script setup>
import { ref, watch, computed } from "vue";
import { formatDate } from "@/Helpers/formatDate";
import { useRequest } from "@/Composables/useRequest";
const { isLoading, requests } = useRequest();
import axios from "axios";

const props = defineProps({
  show: Boolean,
  idLead: {
    type: String,
    default: null,
  },
});
const emit = defineEmits(["update:show"]);

const activities = ref([]);
const leads = ref(null);

const close = () => {
  emit("update:show", false);
  activities.value = [];
  leads.value = null;
};

const loadLead = async () => {
  return requests(
    async () => {
      const { data } = await axios.get(route("leads.show", props.idLead));
      const cleanData = data.data;
      leads.value = cleanData || null;
      console.log(cleanData);
    },
    "Gagal mengambil data produk",
    false
  );
};

const loadActivities = async (page = 1) => {
  if (!props.idLead) return;
  return requests(
    async () => {
      const { data } = await axios.get(route("leads.activities", props.idLead), {
        params: {
          page,
        },
      });
      const cleanData = data.data;
      activities.value = cleanData;
      console.log(cleanData);
    },
    "Gagal mengambil data aktivitas lead",
    false
  );
};
const loadInitialData = async () => {
  if (!props.idLead) return;
  isLoading.value = true;
  try {
    await Promise.all([loadActivities(), loadLead()]);
  } catch (error) {
    console.error("Gagal memuat data :", error);
  } finally {
    isLoading.value = false;
  }
};
watch(
  () => props.show,
  async (isOpen) => {
    if (isOpen) {
      await loadInitialData();
    }
  },
  {
    immediate: true,
  }
);

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
  return (
    roles[roleName] || {
      label: "User",
      color: "bg-light text-dark border",
    }
  );
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
const statusProspect = (value) => {
  const types = {
    NEW: {
      label: "Prospek Baru Masuk",
      color: "bg-info text-white",
    },
    FOLLOW_UP: {
      label: "Sedang Follow Up",
      color: "bg-primary text-white",
    },
    CLOSED_WON: {
      label: "Berhasil Closing",
      color: "bg-success text-white",
    },
    CLOSED_LOST: {
      label: "Tidak Jadi/Refund",
      color: "bg-secondary text-white",
    },
  };
  return (
    types[value] || {
      label: "Pelanggan Baru",
      color: "bg-light text-dark border",
    }
  );
};
const getShortDate = (date) => {
  if (!date) return "";
  const d = new Date(date);
  return d.toLocaleDateString("id-ID", { day: "numeric", month: "short" });
};
const getTimeOnly = (date) => {
  if (!date) return "";
  const d = new Date(date);
  return d.toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit" });
};
const checkUpdateById = () => {};
</script>

<template>
  <div v-if="props.show">
    <modal
      width="850px"
      size="modal-lg"
      :icon="['text-primary', 'bi bi-info-circle']"
      :show="props.show"
      title="Informasi Detail Leads"
      @closed="close"
      :mobileFullscreen="false"
      :footer="false"
    >
      <template #body>
        <div
          v-if="isLoading"
          class="d-flex flex-column align-items-center justify-content-center p-5"
        >
          <div class="spinner-border text-primary mb-3"></div>

          <div class="fw-semibold">Sedang mengambil data...</div>

          <small class="text-muted"> Mohon tunggu sebentar </small>
        </div>

        <div v-else class="timeline-container">
          <div
            class="card bg-info bg-opacity-10 border rounded-4 mb-2 overflow-auto animate-fade-in"
          >
            <div class="card-body p-4">
              <!-- Avatar, Nama, Kode Customer, Status Member & Tag -->
              <div
                class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3"
              >
                <div class="d-flex align-items-start gap-3">
                  <div
                    class="flex-shrink-0 bg-primary-subtle text-primary rounded-circle d-flex justify-content-center align-items-center shadow-sm position-relative"
                    style="width: 58px; height: 58px"
                  >
                    <i class="bi bi-person-fill fs-3"></i>

                    <span
                      v-if="leads.customers?.is_member"
                      class="position-absolute bottom-0 end-0 bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center border border-2 border-white shadow-sm"
                      style="width: 22px; height: 22px"
                      title="Member Aktif"
                    >
                      <i class="bi bi-star-fill" style="font-size: 10px"></i>
                    </span>
                  </div>

                  <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                      <h5 class="fw-bold text-dark mb-1 text-capitalize">
                        {{ leads.customers?.name }}
                      </h5>
                      <span
                        v-if="leads.customers?.customer_code"
                        class="badge bg-white text-secondary border border-secondary border-opacity-25 font-monospace fw-semibold px-2 py-1 rounded-2"
                        style="font-size: 11px"
                      >
                        <i class="bi bi-upc-scan me-1 text-primary"></i
                        >{{ leads.customers.customer_code }}
                      </span>
                    </div>

                    <div
                      class="d-flex flex-wrap gap-3 text-muted mt-1"
                      style="font-size: 13px"
                    >
                      <span class="d-flex align-items-center">
                        <i class="bi bi-telephone-fill me-1 text-primary opacity-75"></i>
                        {{ leads.customers?.phone || "-" }}
                      </span>
                      <span class="d-flex align-items-center">
                        <i class="bi bi-envelope-fill me-1 text-primary opacity-75"></i>
                        {{ leads.customers?.email || "Tidak ada email" }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Badge Status Member & Customer Tag di Kanan Atas -->
                <div class="d-flex flex-wrap align-items-center gap-1 align-self-start">
                  <!-- Badge Member / Non-Member -->
                  <span
                    class="badge rounded-pill px-2 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm"
                    :class="
                      leads.customers?.is_member
                        ? 'bg-warning text-dark border border-warning'
                        : 'bg-white text-secondary border border-secondary border-opacity-25'
                    "
                    style="font-size: 12px"
                  >
                    <i
                      class="bi"
                      :class="
                        leads.customers?.is_member
                          ? 'bi-patch-check-fill text-dark'
                          : 'bi-person'
                      "
                    ></i>
                    {{ leads.customers?.is_member ? "Member" : "Non-Member" }}
                  </span>

                  <!-- Badge Customer Tag -->
                  <span
                    class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 fw-semibold text-capitalize shadow-sm"
                    style="font-size: 12px"
                  >
                    <i class="bi bi-tag-fill me-1"></i>
                    {{ customerTagMeta(leads.customers?.customer_tag).label }}
                  </span>
                </div>
              </div>

              <div
                class="mt-3 p-3 bg-white bg-opacity-75 border border-white rounded-3 shadow-sm"
              >
                <div class="row g-2 align-items-center" style="font-size: 12px">
                  <!-- Alamat Lengkap -->
                  <div class="col-12 col-md-8 d-flex align-items-start gap-2">
                    <i class="bi bi-geo-alt-fill text-danger mt-1 flex-shrink-0"></i>
                    <div>
                      <div
                        class="text-muted fw-semibold text-uppercase"
                        style="font-size: 10px; letter-spacing: 0.5px"
                      >
                        Alamat Pelanggan
                      </div>
                      <div class="text-dark fw-medium lh-sm mt-1">
                        {{ leads.customers?.address || "Alamat belum diisi" }}
                      </div>
                    </div>
                  </div>

                  <!-- Kota / Domisili -->
                  <div
                    class="col-12 col-md-4 d-flex align-items-start gap-2 border-start-md ps-md-3"
                  >
                    <i class="bi bi-building text-info mt-1 flex-shrink-0"></i>
                    <div>
                      <div
                        class="text-muted fw-semibold text-uppercase"
                        style="font-size: 10px; letter-spacing: 0.4px"
                      >
                        Kota / Kabupaten
                      </div>
                      <div class="text-dark fw-bold mt-1 text-capitalize">
                        {{ leads.customers?.city || "-" }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-if="leads.notes"
                class="mt-3 p-3 bg-light border-start border-4 border-warning rounded-end-3 overflow-hidden"
              >
                <div class="d-flex align-items-start gap-2">
                  <i class="bi bi-journal-text text-warning mt-1 fs-5 flex-shrink-0"></i>
                  <div class="flex-grow-1" style="min-width: 0">
                    <div
                      class="text-muted fw-bold text-uppercase tracking-wide mb-1"
                      style="font-size: 11px"
                    >
                      Catatan
                    </div>
                    <p
                      class="mb-0 text-dark"
                      style="
                        font-size: 13.5px;
                        white-space: pre-line;
                        line-height: 1.5;
                        word-break: break-word;
                        overflow-wrap: break-word;
                      "
                    >
                      {{ leads.notes }}
                    </p>
                  </div>
                </div>
              </div>

              <hr class="border-secondary opacity-10 my-3" />

              <!-- List Produk -->
              <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6
                    class="fw-bold text-dark text-uppercase mb-0"
                    style="font-size: 12.5px; letter-spacing: 0.5px"
                  >
                    <i class="bi bi-bag-check-fill text-success me-2 fs-6"></i> Daftar
                    Produk Diminati
                  </h6>

                  <span
                    class="badge bg-primary bg-gradient px-2 py-1 rounded-pill shadow-sm"
                  >
                    {{ leads.product_count || 0 }} Produk
                  </span>
                </div>

                <div
                  v-if="leads.products && leads.products.length > 0"
                  class="d-flex align-items-center flex-wrap gap-2"
                >
                  <!-- Limit 4 Produk Pertama -->
                  <template
                    v-for="(prod, index) in leads.products.slice(0, 4)"
                    :key="prod.id_product || index"
                  >
                    <span
                      class="badge bg-white text-success border border-success border-opacity-25 fw-medium py-2 px-3 rounded-pill d-flex align-items-center shadow-sm hover-shadow transition-all"
                      style="font-size: 12.5px"
                    >
                      <i class="bi bi-box-seam me-2 opacity-75"></i>
                      <span
                        class="text-truncate"
                        style="max-width: 180px"
                        :title="prod.product_name"
                      >
                        {{ prod.product_name }}
                      </span>
                    </span>
                  </template>

                  <!-- Sisa Produk (Jika lebih dari 4) -->
                  <template v-if="leads.products.length > 4">
                    <span
                      class="badge bg-white text-dark border border-secondary border-opacity-25 fw-bold py-2 px-3 rounded-pill shadow-sm hover-shadow transition-all cursor-pointer"
                      style="font-size: 12px"
                      :title="
                        leads.products
                          .slice(4)
                          .map((p) => p.product_name)
                          .join('\n')
                      "
                    >
                      +{{ leads.products.length - 4 }} Lainnya
                    </span>
                  </template>
                </div>

                <!-- State Jika Tidak Ada Produk -->
                <div v-else>
                  <span
                    class="badge bg-light text-muted border border-secondary border-opacity-10 fw-normal py-1 px-3 rounded-pill fst-italic"
                    style="font-size: 12px"
                  >
                    <i class="bi bi-inbox me-1"></i> Belum ada produk
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 mb-4 animate-fade-in">
            <!-- Channel Trafik -->
            <div
              class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm"
              style="font-size: 12px"
            >
              <div
                class="bg-light rounded-circle d-flex justify-content-center align-items-center me-2"
                style="width: 24px; height: 24px"
              >
                <i :class="'bi ' + leads.channel?.icon" style="font-size: 14px"></i>
              </div>
              <span class="text-muted me-1">Sumber:</span>
              <span class="fw-bold text-dark text-capitalize">{{
                leads.channel?.name || "-"
              }}</span>
            </div>

            <!-- Suhu -->
            <div
              class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm"
              style="font-size: 12px"
            >
              <div
                class="bg-warning-subtle text-warning rounded-circle d-flex justify-content-center align-items-center me-2"
                style="width: 24px; height: 24px"
              >
                <i class="bi bi-thermometer-half"></i>
              </div>
              <span class="text-muted me-1">Suhu:</span>
              <span class="fw-bold text-dark text-capitalize">{{
                leads.temperature || "-"
              }}</span>
            </div>

            <!-- Status Prospek -->
            <div
              class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm"
              style="font-size: 12px"
            >
              <div
                class="bg-success-subtle text-success rounded-circle d-flex justify-content-center align-items-center me-2"
                style="width: 24px; height: 24px"
              >
                <i class="bi bi-activity"></i>
              </div>
              <span class="text-muted me-1">Status:</span>
              <span class="fw-bold text-dark text-capitalize">{{
                statusProspect(leads.status).label
              }}</span>
            </div>

            <!-- Tanggal Follow Up -->
            <div
              v-if="leads.follow_up_date"
              class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm"
              style="font-size: 12px"
            >
              <div
                class="bg-info-subtle text-info rounded-circle d-flex justify-content-center align-items-center me-2"
                style="width: 24px; height: 24px"
              >
                <i class="bi bi-calendar-event"></i>
              </div>
              <span class="text-muted me-1">Follow-Up:</span>
              <span class="fw-bold text-dark">{{
                getShortDate(leads.follow_up_date)
              }}</span>
            </div>

            <!-- Dibuat Oleh (PIC / Creator) -->
            <div
              v-if="leads.creator_name"
              class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm"
              style="font-size: 12px"
            >
              <div
                class="bg-secondary-subtle text-secondary rounded-circle d-flex justify-content-center align-items-center me-2"
                style="width: 24px; height: 24px"
              >
                <i class="bi bi-person-check-fill"></i>
              </div>
              <span class="text-muted me-1">PIC:</span>
              <span class="fw-bold text-dark text-capitalize">{{
                leads.creator_name
              }}</span>
            </div>

            <!-- Closed At (Tampil jika sudah ditutup/closing) -->
            <div
              v-if="leads.closed_at"
              class="d-inline-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-sm"
              style="font-size: 12px"
            >
              <div
                class="bg-danger-subtle text-danger rounded-circle d-flex justify-content-center align-items-center me-2"
                style="width: 24px; height: 24px"
              >
                <i class="bi bi-check2-circle"></i>
              </div>
              <span class="text-muted me-1">Selesai:</span>
              <span class="fw-bold text-dark">{{ getShortDate(leads.closed_at) }}</span>
            </div>
          </div>

          <!-- Timeline Activity -->
          <div class="animate-fade-in">
            <h6
              class="fw-bold text-muted mb-3 pb-2 d-flex align-items-center text-uppercase"
              style="font-size: 13px; letter-spacing: 0.5px"
            >
              <i class="bi bi-clock-history me-2 fs-5"></i> Timeline Aktivitas Follow-Up
            </h6>
            <div v-if="activities.length > 0" class="tracking-container pb-3">
              <div
                v-for="(act, index) in activities"
                :key="act.id_lead_activity"
                class="d-flex align-items-stretch gap-3"
              >
                <div
                  class="flex-shrink-0 text-end pe-3"
                  style="width: 60px; padding-top: 2px"
                >
                  <div class="fw-medium tracking-date text-muted">
                    {{ getShortDate(act.created_at) }}
                  </div>
                  <div class="tracking-time text-muted opacity-75">
                    {{ getTimeOnly(act.created_at) }}
                  </div>
                </div>

                <div
                  class="position-relative d-flex flex-column align-items-center"
                  style="width: 5px"
                >
                  <div
                    class="rounded-circle z-2"
                    :class="index === 0 ? 'bg-success' : 'bg-secondary opacity-50'"
                    :style="
                      index === 0
                        ? 'width: 10px; height: 10px; margin-top: 4px; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.2);'
                        : 'width: 8px; height: 8px; margin-top: 5px;'
                    "
                  ></div>

                  <div
                    v-if="index !== activities.length - 1"
                    class="bg-secondary opacity-25 position-absolute"
                    style="
                      width: 2px;
                      top: 16px;
                      bottom: -6px;
                      left: 50%;
                      transform: translateX(-50%);
                      z-index: 1;
                    "
                  ></div>
                </div>

                <div class="flex-grow-1 ps-3 pb-3">
                  <div
                    class="tracking-desc mb-1"
                    :class="index === 0 ? 'text-success fw-medium' : 'text-muted'"
                    style="white-space: pre-line"
                  >
                    {{ act.description }}
                  </div>

                  <div
                    class="tracking-user text-muted opacity-75 d-flex align-items-center gap-1 mt-1"
                  >
                    <i class="bi bi-person"></i>
                    <span class="text-capitalize"
                      >{{ act.creator?.name || "Sistem" }} -
                      {{ getRoleName(act.creator?.role_name).label }}</span
                    >
                    <span
                      v-if="act.updated_by !== act.created_by"
                      class="fst-italic ms-1"
                    >
                      ( Diubah: {{ act.user_update?.name }} -
                      {{ getRoleName(act.user_update?.role_name).label }} ,
                      {{ getShortDate(act.updated_at) }} -
                      {{ getTimeOnly(act.updated_at) }}
                      )
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="empty-state">
              <div class="empty-icon bg-light text-muted mb-3">
                <i class="bi bi-clock-history"></i>
              </div>
              <h6 class="text-muted fw-bold">Belum Ada Riwayat</h6>
              <p class="text-muted small">
                Belum ada jejak rekam atau aktivitas untuk pelanggan ini.
              </p>
            </div>
          </div>
        </div>
      </template>
    </modal>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeInSlideUp 0.35s ease-out forwards;
}

@keyframes fadeInSlideUp {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.timeline-container {
  position: relative;
  max-height: 500px;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0px 8px;
}

/* Kustomisasi Scrollbar agar elegan */
.timeline-container::-webkit-scrollbar {
  width: 6px;
}

.timeline-container::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
}

.timeline-container::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}

.timeline-container::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.tracking-wide {
  letter-spacing: 1px;
}

/* Kustomisasi Empty State */
.empty-state {
  min-height: 260px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px 20px;
}

.empty-state > i {
  font-size: 48px;
  color: #cbd5e1;
  opacity: 0.7;
}

.empty-icon {
  width: 58px;
  height: 58px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
}

/* Tracking Activity UI Styles */
.tracking-container {
  max-height: 520px;
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 8px;
}

.tracking-container::-webkit-scrollbar {
  width: 5px;
}

.tracking-container::-webkit-scrollbar-track {
  background: transparent;
}

.tracking-container::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 10px;
}

.tracking-container::-webkit-scrollbar-thumb:hover {
  background: #cbd5e1;
}

/* Tipografi Presisi */
.tracking-date {
  font-size: 12px;
  line-height: 1.2;
  letter-spacing: 0.2px;
}

.tracking-time {
  font-size: 12px;
  margin-top: 2px;
}

.tracking-desc {
  font-size: 14px;
  line-height: 1.55;
  letter-spacing: 0.1px;
  word-break: break-word;
  white-space: pre-wrap;
  word-wrap: break-word;
  hyphens: auto;
}

.tracking-user {
  font-size: 12px;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Efek terangkat (melayang) tipis saat kursor diarahkan */
.hover-shadow:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08) !important;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
