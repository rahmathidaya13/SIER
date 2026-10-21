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
const page = usePage();
const branchId = computed(() => page.props.auth.user?.branch?.id_branch);
const branchName = computed(() => page.props.auth.user?.branch?.name ?? "Cabang Anda");
const canSelectBranch = computed(() => hasRole(["super_admin", "developer"]));
const customerCodePreview = ref(null);

// Apakah tipe pelanggan adalah institusi (Butuh penekanan PIC & NPWP)
const isInstitution = computed(() =>
  ["company", "government"].includes(form.customer_type)
);

const getDefaultForm = () => ({
  name: "",
  pic_name: "",
  phone: "",
  email: "",
  tax_number: "",
  customer_type: "individual",
  customer_tag: "new",
  business_type: "retail",
  status: "active",
  is_member: false,
  city: "",
  address: "",
  created_of_branch: canSelectBranch.value ? null : branchId.value || null,
});
const form = useForm(getDefaultForm());

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
const customerTagOptions = [
  {
    value: "new",
    label: "Pelanggan Baru",
    icon: "bi-person-plus-fill",
    activeClass: "bg-info text-dark border-info",
    inactiveClass: "text-info-emphasis border-info-subtle bg-info bg-opacity-10",
  },
  {
    value: "repeat",
    label: "Pelanggan Lama",
    icon: "bi-arrow-repeat",
    activeClass: "bg-primary text-white border-primary",
    inactiveClass: "text-primary border-primary-subtle bg-primary bg-opacity-10",
  },
  {
    value: "has_cancelled",
    label: "Pernah Cancel",
    icon: "bi-exclamation-triangle-fill",
    activeClass: "bg-warning text-dark border-warning",
    inactiveClass: "text-warning-emphasis border-warning-subtle bg-warning bg-opacity-10",
  },
];

const businessTypes = [
  { value: "retail", label: "Retail / Toko / UMKM", icon: "bi-shop" },
  { value: "fnb", label: "F&B / Kuliner", icon: "bi-cup-hot-fill" },
  { value: "service", label: "Jasa / Layanan", icon: "bi-tools" },
  { value: "manufacture", label: "Manufaktur", icon: "bi-gear-wide-connected" },
  { value: "other", label: "Lainnya", icon: "bi-grid-fill" },
];

const statusOptions = [
  {
    value: "active",
    label: "Aktif",
    icon: "bi-check-circle-fill",
    activeClass: "bg-success text-white border-success shadow-sm",
    inactiveClass: "text-success border-success-subtle bg-success bg-opacity-10",
  },
  {
    value: "inactive",
    label: "Nonaktif",
    icon: "bi-pause-circle-fill",
    activeClass: "bg-secondary text-white border-secondary shadow-sm",
    inactiveClass: "text-secondary border-secondary-subtle bg-light",
  },
  {
    value: "blacklisted",
    label: "Blacklist",
    icon: "bi-slash-circle-fill",
    activeClass: "bg-danger text-white border-danger shadow-sm",
    inactiveClass: "text-danger border-danger-subtle bg-danger bg-opacity-10",
  },
];

const branchOptions = computed(() => {
  if (canSelectBranch.value) {
    return props.branches.map((data) => ({
      label: data.name,
      value: data.id_branch,
    }));
  }

  const ownBranch = props.branches.filter((data) => data.id_branch === branchId.value);
  if (ownBranch.length > 0) {
    return ownBranch.map((data) => ({
      label: data.name,
      value: data.id_branch,
    }));
  }

  return branchId.value ? [{ label: branchName.value, value: branchId.value }] : [];
});
const loadCustomer = async () => {
  if (!props.idCustomer) {
    resetForm();
    return;
  }

  isLoadingData.value = true;
  try {
    const { data } = await axios.get(route("customer.show", props.idCustomer));
    fillForm(data.data);
  } catch (error) {
    console.error("Gagal mengambil data pelanggan:", error);
    alert("Terjadi kesalahan saat memuat data pelanggan.");
  } finally {
    isLoadingData.value = false;
  }
};
const fillForm = (data) => {
  customerCodePreview.value = data.customer_code || null;
  form.defaults({
    name: data.name || "",
    phone: data.phone || "",
    email: data.email || "",
    customer_type: data.customer_type || "individual",
    customer_tag: data.customer_tag || "new",
    business_type: data.business_type || "retail",
    address: data.address || "",
    created_of_branch:
      data.created_of_branch || (canSelectBranch.value ? null : branchId.value || null),

    pic_name: data.pic_name || "",
    tax_number: data.tax_number,
    status: data.status || "active",
    is_member: Boolean(data.is_member),
    city: data.city || "",
  });
  console.log(data);
  form.reset();
};
const resetForm = () => {
  customerCodePreview.value = null;
  form.defaults(getDefaultForm());
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
        :title="isEdit ? 'Ubah Data Pelanggan' : 'Tambah Pelanggan Baru'"
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
            <div
              class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 pb-3 border-bottom"
            >
              <div>
                <span
                  v-if="isEdit && customerCodePreview"
                  class="badge bg-dark bg-opacity-10 text-dark border px-2 py-2 rounded-pill fw-semibold"
                  style="font-size: 12px"
                >
                  <i class="bi bi-upc-scan me-1"></i> Kode: {{ customerCodePreview }}
                </span>
                <span v-else class="text-muted small">
                  <i class="bi bi-info-circle me-1"></i> Lengkapi profil dan klasifikasi
                  pelanggan di bawah ini.
                </span>
              </div>

              <div
                @click="form.is_member2 = !form.is_member"
                class="member-toggle d-inline-flex align-items-center gap-2 px-2 py-2 rounded-pill border cursor-pointer transition-all align-self-start align-self-sm-auto"
                :class="
                  form.is_member
                    ? 'bg-warning bg-opacity-25 border-warning border-opacity-25 text-dark shadow-sm'
                    : 'bg-light border-light-subtle text-muted'
                "
              >
                <i
                  class="bi fs-6"
                  :class="
                    form.is_member
                      ? 'bi-star-fill text-warning'
                      : 'bi-star text-secondary'
                  "
                ></i>
                <span class="fw-bold" style="font-size: 12px">
                  {{ form.is_member ? "Pelanggan Member" : "Non-Member (Reguler)" }}
                </span>
                <div class="form-check form-switch mb-0 ms-1">
                  <input
                    class="form-check-input cursor-pointer"
                    type="checkbox"
                    role="switch"
                    v-model="form.is_member"
                    @click.stop
                    name="is_member"
                  />
                </div>
              </div>
            </div>

            <!-- BAGIAN 1: KATEGORI PELANGGAN -->
            <div class="mb-3">
              <label
                for="customer_type"
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
                    class="choice-card p-2 rounded-3 border d-flex align-items-center gap-2 cursor-pointer transition-all h-100"
                    :class="
                      form.customer_type === type.value
                        ? 'choice-active border-primary bg-primary bg-opacity-10 shadow-sm'
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
              <input-error :message="form.errors.customer_type" />
            </div>

            <!-- BAGIAN 2: IDENTITAS & KONTAK -->
            <div
              class="p-3 bg-light bg-opacity-50 rounded-4 border border-light-subtle mb-3"
            >
              <div class="row g-3">
                <!-- Nama Pelanggan -->
                <div :class="isInstitution ? 'col-12 col-md-6' : 'col-12'">
                  <label
                    class="form-label d-flex justify-content-between align-items-center mb-2"
                  >
                    <span class="text-uppercase text-muted fw-bold tracking-wide">
                      {{
                        isInstitution
                          ? "Nama Perusahaan / Instansi"
                          : "Nama Lengkap Pelanggan"
                      }}
                      <span class="text-danger">*</span>
                    </span>
                    <small class="text-muted" style="font-size: 11px">
                      {{ form.name.length }}/150
                    </small>
                  </label>

                  <div class="position-relative">
                    <span class="input-icon-left">
                      <i class="bi bi-person-badge"></i>
                    </span>
                    <input-text
                      v-model="form.name"
                      name="name"
                      :error="form.errors.name"
                      :placeholder="
                        isInstitution
                          ? 'Cth: PT. Maju Bersama / Dinas Pendidikan'
                          : 'Cth: Budi Santoso'
                      "
                      inputClass="input-fixed-height"
                    />
                  </div>
                  <input-error :message="form.errors.name" />
                </div>

                <div :class="isInstitution ? 'col-12 col-md-6' : 'col-12 col-md-6'">
                  <label
                    class="form-label d-flex justify-content-between align-items-center mb-2"
                  >
                    <div class="text-uppercase text-muted fw-bold tracking-wide">
                      Nama PIC / Narahubung
                    </div>
                    <span
                      class="badge fw-normal"
                      :class="
                        isInstitution
                          ? 'bg-info bg-opacity-10 text-info-emphasis border border-info-subtle'
                          : 'bg-secondary bg-opacity-10 text-secondary'
                      "
                      style="font-size: 8px"
                    >
                      {{ isInstitution ? "Disarankan" : "Opsional" }}
                    </span>
                  </label>

                  <div class="position-relative">
                    <span class="input-icon-left">
                      <i class="bi bi-person-lines-fill"></i>
                    </span>
                    <input-text
                      v-model="form.pic_name"
                      name="pic_name"
                      :error="form.errors.pic_name"
                      placeholder="Nama perwakilan yang dihubungi"
                      inputClass="input-fixed-height"
                    />
                  </div>
                  <input-error :message="form.errors.pic_name" />
                </div>

                <!-- Nomor Telepon / WhatsApp -->
                <div :class="isInstitution ? 'col-12 col-md-4' : 'col-12 col-md-6'">
                  <label class="form-label d-flex align-items-center mb-2">
                    <div class="text-uppercase text-muted fw-bold tracking-wide">
                      No. Handphone <span class="text-danger">*</span>
                    </div>
                  </label>
                  <div class="position-relative">
                    <span class="input-icon-left">
                      <i class="bi bi-whatsapp"></i>
                    </span>
                    <input-number
                      v-model="form.phone"
                      name="phone"
                      :error="form.errors.phone"
                      placeholder="081234567890"
                      inputClass="input-fixed-height"
                      :maxChar="15"
                    />
                  </div>
                  <input-error :message="form.errors.phone" />
                </div>

                <div :class="isInstitution ? 'col-12 col-md-4' : 'col-12 col-md-6'">
                  <label
                    class="form-label d-flex justify-content-between align-items-center mb-2"
                  >
                    <div class="text-uppercase text-muted fw-bold tracking-wide">
                      Alamat Email
                    </div>
                    <span
                      class="badge bg-secondary bg-opacity-10 text-secondary fw-normal"
                      style="font-size: 10px"
                    >
                      Opsional
                    </span>
                  </label>
                  <div class="position-relative">
                    <span class="input-icon-left">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input-text
                      type="email"
                      v-model="form.email"
                      name="email"
                      :error="form.errors.email"
                      placeholder="pelanggan@email.com"
                      inputClass="input-fixed-height"
                    />
                  </div>
                  <input-error :message="form.errors.email" />
                </div>

                <div :class="isInstitution ? 'col-12 col-md-4' : 'col-12 col-md-6'">
                  <label
                    class="form-label d-flex justify-content-between align-items-center mb-2"
                  >
                    <div class="text-uppercase text-muted fw-bold tracking-wide">
                      NPWP
                    </div>
                    <span
                      class="badge bg-secondary bg-opacity-10 text-secondary fw-normal"
                      style="font-size: 10px"
                    >
                      Opsional
                    </span>
                  </label>
                  <div class="position-relative">
                    <span class="input-icon-left">
                      <i class="bi bi-card-heading"></i>
                    </span>
                    <input-number
                      v-model="form.tax_number"
                      name="tax_number"
                      :error="form.errors.tax_number"
                      placeholder="00.000.000.0-000.000"
                      inputClass="input-fixed-height"
                      :maxChar="30"
                    />
                  </div>
                  <input-error :message="form.errors.tax_number" />
                </div>
              </div>
            </div>

            <!-- BAGIAN 3: SEKTOR BISNIS -->

            <div class="row g-3 mb-3">
              <div class="col-12">
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
                    class="btn btn-sm rounded-pill px-2 py-2 d-inline-flex align-items-center gap-2 transition-all"
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
                <input-error :message="form.errors.business_type" />
              </div>

              <div class="col-12">
                <label
                  class="form-label text-uppercase text-muted fw-bold tracking-wide mb-2"
                >
                  Kategori Pelanggan <span class="text-danger">*</span>
                </label>
                <div class="d-flex gap-2">
                  <button
                    v-for="tag in customerTagOptions"
                    :key="tag.value"
                    type="button"
                    @click="form.customer_tag = tag.value"
                    class="btn btn-sm flex-fill rounded-pill py-2 px-2 border d-inline-flex align-items-center justify-content-center gap-1 transition-all fw-semibold"
                    :class="
                      form.customer_tag === tag.value
                        ? tag.activeClass
                        : tag.inactiveClass
                    "
                  >
                    <i class="bi" :class="tag.icon"></i>
                    <span>{{ tag.label }}</span>
                  </button>
                </div>
                <input-error :message="form.errors.customer_tag" />
              </div>

              <div class="col-12">
                <label
                  class="form-label text-uppercase text-muted fw-bold tracking-wide mb-2"
                >
                  Status Pelanggan <span class="text-danger">*</span>
                </label>
                <div class="d-flex gap-1">
                  <button
                    v-for="st in statusOptions"
                    :key="st.value"
                    type="button"
                    @click="form.status = st.value"
                    class="btn btn-sm flex-fill rounded-pill py-2 px-2 border d-inline-flex align-items-center justify-content-center gap-1 transition-all fw-semibold"
                    :class="form.status === st.value ? st.activeClass : st.inactiveClass"
                  >
                    <i class="bi" :class="st.icon"></i>
                    <span>{{ st.label }}</span>
                  </button>
                </div>
                <input-error :message="form.errors.status" />
              </div>
            </div>
            <div class="row g-3">
              <div class="col-12">
                <label
                  class="form-label d-flex justify-content-between align-items-center mb-2"
                >
                  <span class="text-uppercase text-muted fw-bold tracking-wide">
                    Terdaftar di Cabang <span class="text-danger">*</span>
                  </span>

                  <span
                    v-if="!canSelectBranch"
                    class="badge bg-secondary bg-opacity-10 text-secondary fw-normal d-inline-flex align-items-center gap-1"
                    style="font-size: 10px"
                  >
                    <i class="bi bi-lock-fill"></i> Sesuai Cabang Akun
                  </span>
                </label>
                <select-input
                  text="Pilih Cabang"
                  name="created_of_branch"
                  v-model="form.created_of_branch"
                  :error="form.errors.created_of_branch"
                  :options="branchOptions"
                  :disabled="!canSelectBranch"
                />
                <input-error :message="form.errors.created_of_branch" class="mt-1" />
              </div>

              <div class="col-12">
                <label class="form-label d-flex align-items-center mb-2">
                  <span class="text-uppercase text-muted fw-bold tracking-wide">
                    Kota / Kabupaten <span class="text-danger">*</span>
                  </span>
                </label>
                <div class="position-relative">
                  <span class="input-icon-left">
                    <i class="bi bi-pin-map"></i>
                  </span>
                  <input-text
                    v-model="form.city"
                    name="city"
                    :error="form.errors.city"
                    placeholder="Cth: Pekanbaru, Jakarta Selatan"
                    inputClass="input-fixed-height"
                  />
                </div>
                <input-error :message="form.errors.city" />
              </div>

              <!-- Alamat Lengkap -->
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <label
                    class="form-label text-uppercase text-muted fw-bold tracking-wide"
                  >
                    Alamat Lengkap <span class="text-danger">*</span>
                  </label>
                </div>

                <text-area
                  v-model="form.address"
                  name="address"
                  :rows="3"
                  :maxChar="255"
                  :error="form.errors.address"
                  placeholder="Masukkan alamat yang sesuai..."
                ></text-area>
                <input-error :message="form.errors.address" class="mt-1" />
              </div>
            </div>
          </form-wrapper>
        </template>

        <template #footer>
          <div
            class="d-flex justify-content-end gap-2 p-2 bg-transparent"
          >
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
              :disabled="form.processing || isLoadingData"
              class="btn btn-height-1 fw-semibold rounded-3 shadow-sm d-inline-flex align-items-center gap-2"
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
.choice-card,
.member-toggle {
  user-select: none;
}
.hover-bg-light:hover {
  background-color: #f8fafc !important;
  border-color: #cbd5e1 !important;
}
.choice-active {
  box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
}
.invalid-feedback {
  font-size: 13px;
}
</style>
