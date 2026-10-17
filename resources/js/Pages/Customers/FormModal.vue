<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from "@/Composables/useAuth";
import axios from "axios";
const props = defineProps({
  idCustomer: {
    type: String,
    default: null,
  },
  branches: {
    type: Array,
    default: () => [],
  },
  show: Boolean,
});
const emit = defineEmits(["update:show"]);
const isEdit = computed(() => !!props.idCustomer);
const isLoadingData = ref(false);
const form = useForm({
  name: "",
  phone: "",
  email: "",
  customer_type: "individual",
  business_type: "retail",
  address: "",
  created_of_branch: "",
});

const customerTypes = [
  {
    value: "individual",
    label: "Perorangan",
    desc: "Pembeli individu / pribadi",
    icon: "bi-person-fill",
    color: "text-primary",
    bg: "bg-primary",
  },
  {
    value: "company",
    label: "Perusahaan",
    desc: "PT, CV, atau Badan Usaha",
    icon: "bi-building-fill",
    color: "text-info",
    bg: "bg-info",
  },
  {
    value: "government",
    label: "Pemerintahan",
    desc: "Instansi / Dinas / BUMN",
    icon: "bi-bank2",
    color: "text-warning",
    bg: "bg-warning",
  },
];

const businessTypes = [
  { value: "retail", label: "Retail / Toko", icon: "bi-shop" },
  { value: "fnb", label: "F&B / Kuliner", icon: "bi-cup-hot-fill" },
  { value: "service", label: "Jasa / Layanan", icon: "bi-tools" },
  { value: "manufacture", label: "Manufaktur", icon: "bi-gear-wide-connected" },
  { value: "other", label: "Lainnya", icon: "bi-grid-fill" },
];
const loadCustomer = async () => {
  if (!props.idCustomer) {
    resetForm();
    return;
  }

  isLoadingData.value = true;
  try {
    const { data } = await axios.get(`/customer/show/${props.idCustomer}`);
    fillForm(data);
  } catch (error) {
    console.error("Gagal mengambil data pelanggan:", error);
    alert("Terjadi kesalahan saat memuat data pelanggan.");
  } finally {
    isLoadingData.value = false;
  }
};
const fillForm = (data) => {
  form.defaults({
    name: data.name || "",
    phone: data.phone || "",
    email: data.email || "",
    customer_type: data.customer_type || "individual",
    business_type: data.business_type || "retail",
    address: data.address || "",
    created_of_branch: data.created_of_branch || "",
  });
  form.reset();
};
const resetForm = () => {
  form.defaults({
    name: "",
    phone: "",
    email: "",
    customer_type: "individual",
    business_type: "retail",
    address: "",
    created_of_branch: "",
  });
  form.reset();
  form.clearErrors();
};
const submit = () => {
  const url = isEdit.value
    ? route("customer.update", props.idCustomer)
    : route("customer.store");

  const transformData = isEdit.value
    ? (data) => ({ ...data, _method: "put" })
    : (data) => data;

  form.transform(transformData).post(url, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
    onSuccess: () => {
      close();
      resetForm();
    },
  });
};

watch(
  () => props.show,
  async (isOpen) => {
    if (!isOpen) return;
    if (props.idCustomer) {
      await loadCustomer();
    } else {
      resetForm();
    }
  },
  { immediate: true }
);

const close = () => {
  emit("update:show", false);
  resetForm();
};
const submitTextButton = computed(() => {
  if (form.processing) return "Memproses...";
  return isEdit.value ? "Simpan Perubahan" : "Simpan Pelanggan";
});

// Filter input telepon agar hanya angka & maksimal 15 digit sesuai schema
const handlePhoneInput = (e) => {
  form.phone = e.target.value.replace(/\D/g, "").slice(0, 15);
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
            'bi bi-person-plus-fill text-primary': !isEdit,
          },
        ]"
        :show="props.show"
        :title="isEdit ? 'Edit Data Pelanggan' : 'Tambah Pelanggan Baru'"
        :mobileFullscreen="false"
        @closed="close"
      >
        <template #body>
          <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

          <div v-if="isLoadingData" class="py-5 text-center">
            <div class="spinner-border text-primary mb-2" role="status"></div>
            <p class="text-muted small mb-0">Memuat informasi pelanggan...</p>
          </div>

          <form-wrapper v-else @submit.prevent="submit">
            <!-- BAGIAN 1: KATEGORI PELANGGAN -->
            <div class="mb-4">
              <label
                class="form-label text-uppercase text-muted fw-bold tracking-wide mb-2"
              >
                Tipe Pelanggan <span class="text-danger">*</span>
              </label>
              <div class="row g-2">
                <div
                  v-for="type in customerTypes"
                  :key="type.value"
                  class="col-12 col-sm-4"
                >
                  <div
                    @click="form.customer_type = type.value"
                    class="choice-card p-2.5 rounded-3 border d-flex align-items-center gap-2.5 cursor-pointer transition-all h-100"
                    :class="
                      form.customer_type === type.value
                        ? 'choice-active border-primary bg-primary bg-opacity-10 shadow-2xs'
                        : 'bg-white hover-bg-light'
                    "
                  >
                    <div
                      class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                      :class="[type.bg, 'bg-opacity-10', type.color]"
                      style="width: 38px; height: 38px"
                    >
                      <i class="bi fs-5" :class="type.icon"></i>
                    </div>
                    <div class="min-w-0">
                      <div class="fw-bold text-dark" style="font-size: 13px">
                        {{ type.label }}
                      </div>
                      <div class="text-muted text-truncate" style="font-size: 11px">
                        {{ type.desc }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <input-error :message="form.errors.customer_type" class="mt-1" />
            </div>

            <!-- BAGIAN 2: IDENTITAS & KONTAK -->
            <div
              class="p-3 bg-light bg-opacity-50 rounded-4 border border-light-subtle mb-4"
            >
              <div class="row g-3">
                <!-- Nama Pelanggan -->
                <div class="col-12">
                  <label
                    class="form-label text-uppercase text-muted fw-bold tracking-wide mb-1.5"
                  >
                    Nama Lengkap / Instansi <span class="text-danger">*</span>
                  </label>
                  <div class="input-group focus-wrapper rounded-3">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                      <i class="bi bi-person-badge"></i>
                    </span>
                    <input-text
                      v-model="form.name"
                      name="name"
                      :error="form.errors.name"
                      placeholder="Contoh: Budi Santoso / PT. Maju Jaya"
                      inputClass="border-start-0 ps-0 text-capitalize shadow-none"
                    />
                  </div>
                  <input-error :message="form.errors.name" class="mt-1" />
                </div>

                <!-- Nomor Telepon / WhatsApp -->
                <div class="col-12 col-md-6">
                  <label
                    class="form-label text-uppercase text-muted fw-bold tracking-wide mb-1.5"
                  >
                    No. Telepon / WhatsApp <span class="text-danger">*</span>
                  </label>
                  <div class="input-group focus-wrapper rounded-3">
                    <span class="input-group-text bg-white border-end-0 text-success">
                      <i class="bi bi-whatsapp"></i>
                    </span>
                    <input-text
                      v-model="form.phone"
                      name="phone"
                      :error="form.errors.phone"
                      placeholder="081234567890"
                      @input="handlePhoneInput"
                      inputClass="border-start-0 ps-0 shadow-none"
                    />
                  </div>
                  <input-error :message="form.errors.phone" class="mt-1" />
                </div>

                <!-- Alamat Email -->
                <div class="col-12 col-md-6">
                  <label
                    class="form-label d-flex justify-content-between align-items-center mb-1.5"
                  >
                    <span class="text-uppercase text-muted fw-bold tracking-wide">
                      Alamat Email
                    </span>
                    <span
                      class="badge bg-secondary bg-opacity-10 text-secondary fw-normal"
                      style="font-size: 10px"
                    >
                      Opsional
                    </span>
                  </label>
                  <div class="input-group focus-wrapper rounded-3">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input-text
                      type="email"
                      v-model="form.email"
                      name="email"
                      :error="form.errors.email"
                      placeholder="pelanggan@email.com"
                      inputClass="border-start-0 ps-0 shadow-none"
                    />
                  </div>
                  <input-error :message="form.errors.email" class="mt-1" />
                </div>
              </div>
            </div>

            <!-- BAGIAN 3: SEKTOR BISNIS -->
            <div class="mb-4">
              <label
                class="form-label text-uppercase text-muted fw-bold tracking-wide mb-2"
              >
                Sektor Bisnis <span class="text-danger">*</span>
              </label>
              <div class="d-flex flex-wrap gap-2">
                <button
                  v-for="biz in businessTypes"
                  :key="biz.value"
                  type="button"
                  @click="form.business_type = biz.value"
                  class="btn btn-sm rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2 transition-all"
                  :class="
                    form.business_type === biz.value
                      ? 'btn-primary shadow-sm fw-semibold'
                      : 'btn-light border text-secondary bg-white'
                  "
                >
                  <i class="bi" :class="biz.icon"></i>
                  <span>{{ biz.label }}</span>
                </button>
              </div>
              <input-error :message="form.errors.business_type" class="mt-1" />
            </div>

            <!-- BAGIAN 4: CABANG (Khusus Super Admin jika branches dikirim via props) & ALAMAT -->
            <div class="row g-3">
              <div v-if="hasRole(['super_admin']) && branches.length > 0" class="col-12">
                <label
                  class="form-label text-uppercase text-muted fw-bold tracking-wide mb-1.5"
                >
                  Terdaftar di Cabang <span class="text-danger">*</span>
                </label>
                <select-input
                  text="Pilih Cabang"
                  name="created_of_branch"
                  v-model="form.created_of_branch"
                  :error="form.errors.created_of_branch"
                  :options="branches.map((b) => ({ label: b.name, value: b.id_branch }))"
                />
                <input-error :message="form.errors.created_of_branch" class="mt-1" />
              </div>

              <!-- Alamat Lengkap -->
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-1.5">
                  <label
                    class="form-label text-uppercase text-muted fw-bold tracking-wide mb-0"
                  >
                    Alamat Lengkap <span class="text-danger">*</span>
                  </label>
                  <small
                    class="text-muted"
                    :class="{ 'text-danger fw-bold': form.address.length >= 250 }"
                    style="font-size: 11px"
                  >
                    {{ form.address.length }}/255
                  </small>
                </div>
                <div class="input-group focus-wrapper rounded-3">
                  <span
                    class="input-group-text bg-white border-end-0 text-muted align-items-start pt-2.5"
                  >
                    <i class="bi bi-geo-alt"></i>
                  </span>
                  <textarea
                    v-model="form.address"
                    name="address"
                    rows="3"
                    maxlength="255"
                    class="form-control border-start-0 ps-0 shadow-none"
                    :class="{ 'is-invalid': form.errors.address }"
                    placeholder="Masukkan nama jalan, nomor bangunan, kecamatan, dan kota..."
                  ></textarea>
                </div>
                <input-error :message="form.errors.address" class="mt-1" />
              </div>
            </div>
          </form-wrapper>
        </template>

        <template #footer>
          <div
            class="d-flex justify-content-end align-items-center gap-2 p-2 bg-transparent w-100"
          >
            <button
              type="button"
              class="btn btn-light border fw-medium px-4 rounded-3"
              @click.prevent="close"
            >
              Batal
            </button>
            <button
              @click.prevent="submit"
              type="button"
              :disabled="form.processing || isLoadingData"
              class="btn btn-height-1 fw-semibold px-4 rounded-3 shadow-sm d-inline-flex align-items-center gap-2"
              :class="isEdit ? 'btn-c-success' : 'btn-c-primary'"
            >
              <span
                v-if="form.processing"
                class="spinner-border spinner-border-sm"
              ></span>
              <i v-else :class="isEdit ? 'bi bi-check-circle' : 'bi bi-save'"></i>
              <span>{{ submitTextButton }}</span>
            </button>
          </div>
        </template>
      </modal>
    </div>
  </div>
</template>
<style scoped>
.tracking-wide {
  letter-spacing: 0.5px;
  font-size: 11px;
}

.cursor-pointer {
  cursor: pointer;
}

.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Kustomisasi Kartu Pilihan Tipe Pelanggan */
.choice-card {
  user-select: none;
}
.hover-bg-light:hover {
  background-color: #f8fafc !important;
  border-color: #cbd5e1 !important;
}
.choice-active {
  box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
}

/* Efek Focus-Within pada Input Group */
.focus-wrapper {
  transition: all 0.2s ease-in-out;
  background-color: #fff;
}
.focus-wrapper:focus-within {
  box-shadow: 0 0 0 3.5px rgba(13, 110, 253, 0.12);
}
.focus-wrapper:focus-within .input-group-text,
.focus-wrapper:focus-within :deep(.form-control),
.focus-wrapper:focus-within textarea.form-control {
  border-color: #86b7fe;
}
.focus-wrapper:focus-within .input-group-text i:not(.text-success) {
  color: #0d6efd !important;
}
:deep(.form-control:focus),
textarea.form-control:focus {
  box-shadow: none !important;
}
</style>
