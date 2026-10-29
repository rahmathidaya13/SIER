<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { useRowAlert } from "@/Composables/useAlert";
import axios from "axios";
const { show, __alert, hide } = useRowAlert();
import { useRequest } from "@/Composables/useRequest";
const { isLoading, requests } = useRequest();
const props = defineProps({
  idLead: {
    type: String,
    default: null,
  },
  show: Boolean,
});

const isEdit = computed(() => !!props.idLead);
const activities = ref([]);

const form = useForm({
  description: "",
});

const fillForm = (data) => {
  form.defaults({
    description: "",
  });

  activities.value = data;
  form.reset();
};

const resetForm = () => {
  form.defaults({
    description: "",
  });
  form.reset();
  form.clearErrors();
  activities.value = [];
  activityEdit.id = null;
};

const loadActivity = async () => {
  isLoading.value = true;

  if (!props.idLead) {
    resetForm();
    return;
  }

  try {
    const { data } = await axios.get(route("leads.activities", props.idLead));
    const cleanData = data.data;
    fillForm(cleanData);
  } catch (error) {
    console.error("Gagal mengambil data:", error);
    alert("Terjadi kesalahan saat memuat data.");
  } finally {
    isLoading.value = false;
  }
};
const activityEdit = reactive({
  id: null,
  new_value: "",
});
const editActivity = (activities) => {
  activityEdit.id = activities.id_lead_activity;
  activityEdit.new_value = activities.description || "";
};
const cancelEditActivity = () => {
  activityEdit.id = null;
  activityEdit.new_value = "";
};
const updateActivity = () => {
  router.put(
    route("leads.activity.update", activityEdit.id),
    {
      description: activityEdit.new_value,
    },
    {
      preserveScroll: true,
      preserveState: true,
      replace: true,
      onSuccess: async () => {
        const activity = activities.value.find(
          (actv) => actv.id_lead_activity === activityEdit.id
        );
        if (activity) {
          activity.description = activityEdit.new_value;
        }
        show({
          id: activityEdit.id,
          message: "Aktivitas berhasil diperbarui",
          type: "success",
          duration: 10000,
        });
        await loadActivity();
        cancelEditActivity();
      },
      onError: (errors) => {
        show({
          id: activityEdit.id,
          message: errors.description,
          type: "danger",
          duration: 10000,
        });
      },
    }
  );
};
const submit = () => {
  const url = route("leads.activity.store", props.idLead);
  form.post(url, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
    onSuccess: async () => {
      // close();
      await loadActivity();
    },
  });
};
// watch untuk kondisi buka modal, jika props.show berubah menjadi true, maka loadActivity akan dijalankan
watch(
  () => props.show,
  async (isOpen) => {
    if (!isOpen) return;
    if (props.idLead) {
      await loadActivity();
    } else {
      resetForm();
    }
  },
  {
    immediate: true,
  }
);
const emit = defineEmits(["update:show", "saved"]);
const close = () => {
  emit("update:show", false);
  resetForm();
  usePage().props.flash.modal_message = null;
};

const submitTextButton = computed(() => {
  if (form.processing) return "Memproses...";
  return isEdit.value ? "Simpan" : "Ubah";
});
const getRoleName = (data, mode = "create") => {
  let role;
  if (mode === "create") {
    role = data.creator.role_name;
  }
  if (mode === "edit") {
    role = data.user_update.role_name;
  }
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
  return roles[role] || "bg-light text-dark border";
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
</script>
<template>
  <div class="row" v-if="props.show">
    <div class="col-12">
      <modal
        width="750px"
        size="modal-lg"
        :icon="[
          'text-coffee-main',
          {
            'bi bi-pencil-square text-primary': isEdit,
            'bi bi-plus-circle-fill text-primary': !isEdit,
          },
        ]"
        :show="props.show"
        title="Aktivitas Follow Up"
        :mobileFullscreen="false"
        @closed="close"
        :footer="false"
      >
        <template #body>
          <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

          <div
            v-if="$page.props.flash.modal_message && !isLoading"
            class="alert alert-c-success py-3 px-3 mb-3 d-flex align-items-center justify-content-between rounded-4"
            style="font-size: 15px"
          >
            <div class="d-flex align-items-center">
              <i class="me-1 fs-6 bi bi-check-circle-fill text-success"></i>
              <span class="fw-medium">{{ $page.props.flash.modal_message }}</span>
            </div>
            <button
              type="button"
              class="btn-close"
              @click="$page.props.flash.modal_message = null"
            ></button>
          </div>

          <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-3 small fw-medium">Sedang mengambil data...</p>
          </div>

          <div v-else>
            <form-wrapper @submit.prevent="submit">
              <div class="row g-3 animate-fade-in">
                <div class="col-12 p-3 rounded-4 border shadow-sm">
                  <div class="position-relative">
                    <label
                      for="activity_input"
                      class="form-label small fw-bold text-primary mb-2 d-flex align-items-center"
                    >
                      <i class="bi bi-chat-right-dots-fill me-2 fs-6"></i> BUAT CATATAN
                      FOLLOW-UP
                    </label>
                    <text-area
                      :maxChar="2500"
                      :error="form.errors.description"
                      name="description"
                      v-model="form.description"
                      :rows="5"
                      inputClass="border-0 rounded-3"
                      placeholder="Tuliskan apa yang baru saja dibicarakan dengan pelanggan di sini..."
                    />
                    <input-error :message="form.errors.description" />
                  </div>

                  <div
                    class="d-flex justify-content-end align-items-center gap-2 bg-transparent mt-3"
                  >
                    <button
                      type="button"
                      class="btn btn-sm btn-c-slate px-4 rounded-1"
                      @click.prevent="close"
                    >
                      Batal
                    </button>
                    <button
                      @click.prevent="submit"
                      type="button"
                      :disabled="form.processing || isLoading"
                      class="btn btn-sm fw-semibold rounded-1"
                      :class="
                        form.status === 'CLOSED_WON' ? 'btn-c-success' : 'btn-c-primary'
                      "
                    >
                      <span>
                        <span
                          v-if="form.processing"
                          class="spinner-border spinner-border-sm me-2"
                        ></span>
                        <i
                          v-else
                          :class="isEdit ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"
                        ></i>
                        {{ submitTextButton }}
                      </span>
                    </button>
                  </div>
                </div>

                <div class="col-12">
                  <h6
                    class="fw-bold text-muted mb-3 pb-2 d-flex align-items-center"
                    style="font-size: 13px; letter-spacing: 0.5px"
                  >
                    <i class="bi bi-clock-history me-2 fs-5"></i> JEJAK REKAM AKTIVITAS
                    FOLLOW-UP
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
                          v-if="__alert.visible && __alert.id === act.id_lead_activity"
                          class="alert py-1 px-2 mb-2 d-flex align-items-center rounded-2"
                          :class="`alert-${__alert.type}`"
                          style="font-size: 11px"
                        >
                          <i
                            :class="
                              __alert.type === 'success'
                                ? 'bi bi-check-circle-fill text-success'
                                : 'bi bi-exclamation-triangle-fill text-danger'
                            "
                            class="me-2"
                          ></i>
                          <span class="fw-medium">{{ __alert.message }}</span>
                        </div>

                        <div
                          v-if="
                            !activityEdit.id || activityEdit.id !== act.id_lead_activity
                          "
                          class="tracking-desc mb-1"
                          :class="index === 0 ? 'text-success' : 'text-muted'"
                          style="white-space: pre-line"
                        >
                          {{ act.description }}
                        </div>

                        <div
                          v-if="activityEdit.id !== act.id_lead_activity"
                          class="tracking-user text-muted opacity-75 d-flex align-items-center gap-1 mt-1"
                        >
                          <i class="bi bi-person"></i>
                          <span class="text-capitalize"
                            >{{ act.creator?.name || "Sistem" }} -
                            {{ getRoleName(act).label }}</span
                          >
                          <span v-if="act.user_update !== null" class="fst-italic ms-1">
                            ( Diubah: {{ act.user_update?.name }} -
                            {{ getRoleName(act, "edit").label }} ,
                            {{ getShortDate(act.updated_at) }} -
                            {{ getTimeOnly(act.updated_at) }}
                            )
                          </span>
                        </div>

                        <div
                          v-if="activityEdit.id === act.id_lead_activity"
                          class="mt-2 mb-2"
                        >
                          <text-area
                            :error="
                              __alert.visible && __alert.id === act.id_lead_activity
                            "
                            :maxChar="2500"
                            name="activity"
                            inputClass="border-secondary-subtle rounded-3"
                            v-model="activityEdit.new_value"
                            :rows="5"
                            placeholder="Buat aktifitas pelanggan..."
                          />
                        </div>

                        <div class="d-flex gap-3">
                          <button
                            v-if="!activityEdit.id"
                            @click.prevent="editActivity(act)"
                            class="btn btn-sm p-0 text-primary text-decoration-none shadow-none tracking-action"
                          >
                            Ubah
                          </button>

                          <template v-else-if="activityEdit.id === act.id_lead_activity">
                            <button
                              @click.prevent="cancelEditActivity"
                              class="btn btn-sm p-0 text-danger text-decoration-none shadow-none tracking-action"
                            >
                              Batal
                            </button>
                            <button
                              @click.prevent="updateActivity"
                              class="btn btn-sm p-0 text-success text-decoration-none shadow-none tracking-action"
                            >
                              Simpan
                            </button>
                          </template>
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
                      Belum ada jejak aktivitas pelanggan ini.
                    </p>
                  </div>
                </div>
              </div>
            </form-wrapper>
          </div>
        </template>

        <template #footer>
          <div class="d-flex justify-content-end gap-2 bg-transparent">
            <button
              type="button"
              class="btn btn-c-slate border-0 fw-medium px-4"
              @click.prevent="close"
            >
              Batal
            </button>
            <button
              @click.prevent="submit"
              type="button"
              :disabled="form.processing || isLoading"
              class="btn btn-height-1 fw-semibold"
              :class="form.status === 'CLOSED_WON' ? 'btn-c-success' : 'btn-c-primary'"
            >
              <span>
                <span
                  v-if="form.processing"
                  class="spinner-border spinner-border-sm me-2"
                ></span>
                <i
                  v-else
                  :class="isEdit ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"
                ></i>
                {{ submitTextButton }}
              </span>
            </button>
          </div>
        </template>
      </modal>
    </div>
  </div>
</template>
<style scoped>
/* Memperhalus animasi masuknya form */
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

/* Modifikasi scrollbar agar cantik di area timeline */
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

/* Efek hover tombol */
.hover-bg-primary-subtle:hover {
  background-color: var(--bs-primary-bg-subtle) !important;
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
  padding-right: 10px;
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
  font-size: 11px;
  margin-top: 2px;
}

.tracking-desc {
  font-size: 14px;
  line-height: 1.2;
  letter-spacing: 0.1px;
  word-break: break-word;
  white-space: pre-wrap;
  word-wrap: break-word;
  hyphens: auto;
}

.tracking-user {
  font-size: 12px;
}

.tracking-action {
  font-size: 11.5px;
  font-weight: 500;
}

.tracking-action:hover {
  text-decoration: underline !important;
}
</style>
