<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useForm, router, usePage, Link } from "@inertiajs/vue3";
import { hasRole } from "@/Composables/useAuth";
import { useRowAlert } from "@/Composables/useAlert";
import { useAsyncSearch } from "@/Composables/useAsyncSearch";
import { currencyFormat } from "@/Helpers/currencyFormat";
import { debounce } from "lodash";
const { show, __alert, hide } = useRowAlert();
import axios from "axios";
const props = defineProps({
  idLead: {
    type: String,
    default: null,
  },
  show: Boolean,
  channels: Array,
});
const isEdit = computed(() => !!props.idLead);
const isLoading = ref(false);
const dataLead = ref(null);

// 1. STATE SAKELAR TAB
const activeTab = ref("prospect");

const getDefaultForm = () => ({
  id_channel: null,
  id_customer: null,
  temperature: null,
  status: null,
  notes: "",
  id_product: [],
  product_name: [],

  // Field Konversi Closing
  deal_price: 0,
  paid_amount: 0,
  payment_status: "LUNAS",
  payment_method: "cash",
  quantity: "",
  bank_name: "",
  notes_sales: "",
});
const form = useForm(getDefaultForm());

// Auto-Kalkulasi Uang
watch(
  () => [form.payment_status, form.deal_price],
  ([newStatus, newDealPrice]) => {
    if (newStatus === "LUNAS") {
      form.paid_amount = newDealPrice;
    } else if (newStatus === "DP" && form.paid_amount === newDealPrice) {
      form.paid_amount = 0;
    }
  }
);

// Mengawasi perubahan Status & Suhu secara bersamaan
watch(
  () => [form.status, form.temperature],
  ([newStatus, newTemp], [oldStatus, oldTemp]) => {
    // A. Jika Status diubah jadi Closing -> Suhu paksa jadi HOT
    if (newStatus === "CLOSED_WON" && oldStatus !== "CLOSED_WON") {
      form.temperature = "HOT";
    }
    // B. Jika Suhu diubah jadi HOT -> Status paksa jadi Closing
    else if (newTemp === "HOT" && oldTemp !== "HOT") {
      form.status = "CLOSED_WON";
    }
    // C. Jika batal/Closing -> Turunkan suhu ke WARM agar konsisten
    else if (
      oldStatus === "CLOSED_WON" &&
      newStatus !== "CLOSED_WON" &&
      newTemp === "HOT"
    ) {
      form.temperature = "WARM";
    }
    // D. Jika suhu diturunkan dari HOT -> Batalkan status Closing
    else if (oldTemp === "HOT" && newTemp !== "HOT" && newStatus === "CLOSED_WON") {
      form.status = "NEW";
    }

    if (form.status === "CLOSED_WON" && form.temperature === "HOT") {
      activeTab.value = "convert_lead";
    } else if (activeTab.value === "convert_lead") {
      // Sembunyikan tab uang jika batal closing
      activeTab.value = "prospect";
    }
  }
);
const fillForm = (data) => {
  form.defaults({
    id_channel: data.channel?.id_channel,
    id_customer: data.customers?.id_customer,
    temperature: data.temperature,

    status: data.status,
    notes: data.notes,
    id_product: data.products?.map((p) => p.id_product) || [],
    product_name: data.products?.map((p) => p.product_name) || [],

    // Reset field konversi
    deal_price: 0,
    paid_amount: 0,
    payment_status: "LUNAS",
    payment_method: "cash",
    quantity: "",

    bank_name: "",
    notes_sales: "",
  });

  dataLead.value = data;
  customerSelect.selected = data.customers || null;
  productSelect.selected = data.products?.map((p) => ({
    id_product: p.id_product,
    name: p.product_name || "Produk Terpilih",
    image_path: p.image_path || "",
    image_link: p.image_link || "",
  }));
  form.reset();
};
const resetForm = () => {
  form.defaults(getDefaultForm());
  form.reset();
  form.clearErrors();
  dataLead.value = null;
  customerSelect.selected = null;
  productSelect.selected = [];
};
const tabList = computed(() => {
  let tabs = [];

  if (isEdit.value) {
    tabs.push({ id: "prospect", name: "📋 Data Prospek" });
  } else {
    tabs.push({ id: "prospect", name: "📋 Data Prospek" });
  }

  //Tab Konversi Uang HANYA diselipkan jika statusnya Deal/Closing
  if (form.status === "CLOSED_WON" && form.temperature === "HOT") {
    tabs.push({ id: "convert_lead", name: "💰 Data Konversi" });
  }
  return tabs;
});

const loadLead = async () => {
  isLoading.value = true;

  if (!props.idLead) {
    resetForm();
    return;
  }

  try {
    const { data } = await axios.get(route("leads.show", props.idLead));
    const cleanData = data.data;
    fillForm(cleanData);
    console.log(cleanData);
  } catch (error) {
    console.error("Gagal mengambil data:", error);
    alert("Terjadi kesalahan saat memuat data.");
  } finally {
    isLoading.value = false;
  }
};

const submit = () => {
  const url = isEdit.value ? route("leads.update", props.idLead) : route("leads.store");

  const transformData = (data) => {
    const payload = {
      ...data,
      ...(isEdit.value && { _method: "put" }),
    };

    // Apakah sedang melakukan closing?
    const isClosing = payload.temperature === "HOT" && payload.status === "CLOSED_WON";

    // Jika bukan closing, kosongkan field transaksi
    if (!isClosing) {
      delete payload.deal_price;
      delete payload.paid_amount;
      delete payload.payment_status;
      delete payload.payment_method;
      delete payload.quantity;
      delete payload.bank_name;
      delete payload.notes_sales;
    }
    return payload;
  };

  form.transform(transformData).post(url, {
    preserveScroll: true,
    preserveState: true,
    replace: true,

    onSuccess: () => {
      close();
      form.reset();
    },
    onError: (errors) => {
      const prospectFields = [
        "id_customer",
        "id_channel",
        "id_product",
        "temperature",
        "status",
        "notes",
      ];
      const convertFields = [
        "deal_price",
        "paid_amount",
        "payment_status",
        "payment_method",
        "deal_at",
        "notes_sales",
      ];

      const isErrorInProspect = Object.keys(errors).some((field) =>
        prospectFields.includes(field)
      );
      const isErrorInConvert = Object.keys(errors).some((field) =>
        convertFields.includes(field)
      );

      // Auto-redirect ke tab yang error
      if (
        isErrorInConvert &&
        form.status === "CLOSED_WON" &&
        form.temperature === "HOT" &&
        activeTab.value !== "convert_lead"
      ) {
        activeTab.value = "convert_lead";
      } else if (isErrorInProspect && activeTab.value !== "prospect") {
        activeTab.value = "prospect";
      }
    },
  });
};
// watch untuk kondisi buka modal, jika props.show berubah menjadi true, maka loadLead akan dijalankan
watch(
  () => props.show,
  async (isOpen) => {
    if (!isOpen) return;
    if (props.idLead) {
      await loadLead();
    } else {
      resetForm();
      activeTab.value = "prospect";
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
};
const submitTextButton = computed(() => {
  if (form.processing) return "Memproses...";
  if (form.status === "CLOSED_WON" && form.temperature === "HOT") return "Proses Closing";
  return isEdit.value ? "Simpan Perubahan" : "Simpan Prospek";
});

// kunci field status dan temperature jika sudah pernah disahkan menjadi prospek HOT & CLOSING
const isLocked = computed(() => {
  if (isEdit.value && dataLead.value?.status === "CLOSED_WON") {
    return true;
  } else if (isEdit.value && dataLead.value?.status === "CLOSED_LOST") {
    return true;
  } else if (isEdit.value && dataLead.value?.status === "FOLLOW_UP") {
    return true;
  } else {
    return false;
  }
});

const tempOptions = computed(() => {
  let options = [
    { label: "🔥 HOT (Siap Closing)", value: "HOT" },
    { label: "⏳ WARM (Masih Mempertimbangkan)", value: "WARM" },
    { label: "❄️ COLD (Minat Rendah)", value: "COLD" },
  ];
  if (isEdit.value) {
    options = [
      { label: "⏳ WARM (Masih Mempertimbangkan)", value: "WARM" },
      { label: "❄️ COLD (Minat Rendah)", value: "COLD" },
    ];
  }
  return options;
});

const prospectOptions = computed(() => {
  let options = [
    { label: "⭐ Baru Masuk", value: "NEW" },
    { label: "✔️ Closing (Terjual)", value: "CLOSED_WON" },
  ];
  if (isEdit.value) {
    options = [{ label: "⭐ Baru Masuk", value: "NEW" }];
  }
  if (form.status === "FOLLOW_UP") {
    options = [{ label: "💬 Sedang di Follow Up", value: "FOLLOW_UP" }];
  }
  if (form.status === "CLOSED_LOST") {
    options = [{ label: "❌ Transaksi dibatalkan", value: "CLOSED_LOST" }];
  }

  return options;
});

const selectedChannel = computed({
  get() {
    return props.channels.find((x) => x.id_channel === form.id_channel) || null;
  },

  set(value) {
    form.id_channel = value?.id_channel ?? null;
  },
});

const productSelect = useAsyncSearch(
  "leads.search.product",
  true,
  (selectedList) => {
    form.id_product = selectedList.map((item) => item.id_product);
  },
  "Produk",
  400
);

const customerSelect = useAsyncSearch(
  "leads.search.customer",
  false,
  (selected) => {
    console.log(selected);
    form.id_customer = selected ? selected.id_customer : "";
  },
  "Pelanggan",
  400
);

const imageSource = (product) => {
  if (product?.image_link) {
    return product?.image_link;
  }

  return `https://ui-avatars.com/api/?name=${encodeURIComponent(product?.product_name)}`;
};
const dpAmount = computed(() => {
  if (!form.deal_price) return 0;
  if (form.payment_status === "DP") {
    return Number(form.deal_price * 0.5);
  }
  return form.deal_price;
});

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
        :title="isEdit ? 'Ubah Data Prospek' : ' Input Prospek Baru'"
        :mobileFullscreen="false"
        @closed="close"
      >
        <template #body>
          <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

          <div v-if="isLoading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted mt-3 small fw-medium">Sedang mengambil data...</p>
          </div>
          <div v-else>
            <div class="celestial-tab-container mb-4">
              <button
                v-for="tab in tabList"
                :key="tab.id"
                type="button"
                @click="activeTab = tab.id"
                class="celestial-tab-btn"
                :class="{ active: activeTab === tab.id }"
              >
                <span>{{ tab.name }}</span>
                <span
                  v-if="tab.badge"
                  class="badge rounded-pill bg-primary ms-2"
                  style="font-size: 10px"
                  >{{ tab.badge }}</span
                >
              </button>
            </div>

            <form-wrapper @submit.prevent="submit">
              <div v-show="activeTab === 'prospect'" class="row g-3 animate-fade-in">
                <div class="col-12">
                  <div class="p-3 border rounded-3 bg-white shadow-sm position-relative">
                    <h6
                      class="form-label fw-bold text-primary mb-3 border-bottom pb-2"
                      style="font-size: 13px"
                    >
                      <i class="bi bi-person-badge me-1"></i> 1. IDENTITAS PELANGGAN
                    </h6>
                    <div class="row">
                      <div class="col-12 mb-3">
                        <div class="d-flex flex-column mb-2">
                          <label class="form-label small fw-bold text-muted">
                            PELANGGAN <span class="text-danger">*</span>
                          </label>

                          <multi-select
                            :class="{ 'is-invalid': form.errors.id_customer }"
                            :multiple="false"
                            placeholder="Ketik minimal 2 huruf nama / No. HP pelanggan..."
                            v-model="customerSelect.selected"
                            :options="customerSelect.options"
                            :loading="customerSelect.loading"
                            track-by="id_customer"
                            label="name"
                            :internal-search="false"
                            :clear-on-select="true"
                            :close-on-select="true"
                            :searchable="true"
                            :show-labels="false"
                            open-direction="bottom"
                            @search-change="customerSelect.search"
                            @update:model-value="customerSelect.onSelect"
                          >
                            <!-- Tampilan daftar saat dropdown terbuka -->
                            <template #option="{ option }">
                              <div
                                class="d-flex justify-content-between align-items-center py-1"
                              >
                                <div>
                                  <div class="fw-bold text-dark text-capitalize">
                                    {{ option.name }}
                                    <span
                                      v-if="option.is_member"
                                      class="badge bg-warning text-dark ms-1"
                                      style="font-size: 9px"
                                    >
                                      ★ Member
                                    </span>
                                  </div>
                                  <small class="text-muted">
                                    <i class="bi bi-whatsapp text-success me-1"></i
                                    >{{ option.phone }}
                                    <span v-if="option.city"> • {{ option.city }}</span>
                                  </small>
                                </div>
                                <span
                                  class="badge bg-light text-secondary border font-monospace"
                                >
                                  {{ option.customer_code }}
                                </span>
                              </div>
                            </template>

                            <!-- Kosongkan singleLabel agar nama tampil di kartu bawah field (Opsional: hapus template ini jika ingin tetap tampil di dalam input juga) -->
                            <template #singleLabel>
                              <span class="text-muted"
                                >Pelanggan telah dipilih di bawah</span
                              >
                            </template>
                          </multi-select>

                          <input-error :message="form.errors.id_customer" />

                          <!-- KARTU PELANGGAN TERPILIH DI BAWAH FIELD -->
                          <div
                            v-if="customerSelect.selected"
                            class="mt-2 p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 d-flex align-items-center justify-content-between gap-2"
                          >
                            <div class="d-flex align-items-center gap-3 min-w-0">
                              <div
                                class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 38px; height: 38px"
                              >
                                <i class="bi bi-person-check-fill"></i>
                              </div>
                              <div class="min-w-0">
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                  <span
                                    class="fw-bold text-dark text-capitalize text-truncate"
                                    style="font-size: 14px"
                                  >
                                    {{ customerSelect.selected.name }}
                                  </span>
                                  <span
                                    v-if="customerSelect.selected.customer_code"
                                    class="badge bg-white text-primary border border-primary-subtle rounded-pill"
                                    style="font-size: 12px"
                                  >
                                    {{ customerSelect.selected.customer_code }}
                                  </span>
                                  <span
                                    v-if="customerSelect.selected.is_member"
                                    class="badge bg-warning text-dark rounded-pill"
                                    style="font-size: 12px"
                                  >
                                    ★ Member
                                  </span>
                                  <span
                                    :class="`badge ${
                                      customerTagMeta(
                                        customerSelect.selected.customer_tag
                                      ).class
                                    } rounded-pill`"
                                  >
                                    <i
                                      :class="`bi ${
                                        customerTagMeta(
                                          customerSelect.selected.customer_tag
                                        ).icon
                                      }`"
                                    ></i>
                                    {{
                                      customerTagMeta(
                                        customerSelect.selected.customer_tag
                                      ).label
                                    }}</span
                                  >
                                </div>

                                <small class="text-muted d-block">
                                  <i class="bi bi-whatsapp text-success me-1"></i
                                  >{{ customerSelect.selected.phone }}
                                  <span v-if="customerSelect.selected.email">
                                    • {{ customerSelect.selected.email }}</span
                                  >
                                </small>
                                <small class="text-muted d-block">
                                  <i class="bi bi-geo-alt-fill text-danger"></i>
                                  {{
                                    `${customerSelect.selected.address} - ${customerSelect.selected.city}`
                                  }}
                                </small>
                              </div>
                            </div>

                            <button
                              type="button"
                              class="btn btn-sm btn-light border text-danger rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                              style="width: 30px; height: 30px"
                              title="Hapus pilihan pelanggan"
                              @click="customerSelect.clear()"
                            >
                              <i class="bi bi-x-lg" style="font-size: 11px"></i>
                            </button>
                          </div>
                        </div>

                        <div
                          v-if="!customerSelect.selected"
                          class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 rounded-3 border-info-subtle small mb-3"
                          role="alert"
                        >
                          <i
                            class="bi bi-info-circle-fill text-info-emphasis fs-6 flex-shrink-0"
                          ></i>
                          <div>
                            <span class="fw-bold">Catatan:</span> Pastikan data pelanggan
                            sudah terdaftar. Jika belum ditemukan, klik
                            <Link
                              class="text-decoration-none"
                              :href="route('customer.index')"
                              >Tambah Pelanggan</Link
                            >
                            untuk mendaftarkan pelanggan baru
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="p-3 border rounded-3 bg-light position-relative">
                    <h6
                      class="form-label fw-bold text-primary mb-3 border-bottom pb-2"
                      style="font-size: 13px"
                    >
                      <i class="bi bi-box-seam me-1"></i> 2. MINAT & SUMBER TRAFIK
                    </h6>
                    <div class="row g-2">
                      <div class="col-12 mb-2">
                        <label class="form-label small fw-bold text-muted"
                          >SUMBER TRAFIK (CHANNEL)
                          <span class="text-danger">*</span>
                        </label>
                        <multi-select
                          :searchable="false"
                          :show-labels="false"
                          :class="{ 'is-invalid': form.errors.id_channel }"
                          v-model="selectedChannel"
                          :options="channels"
                          track-by="id_channel"
                          label="name"
                          placeholder="Pilih Channel"
                        >
                          <template #singleLabel="{ option }">
                            <div class="multiselect-single">
                              <i :class="option.icon"></i>
                              <span>{{ option.name }}</span>
                            </div>
                          </template>

                          <template #option="{ option }">
                            <div class="multiselect-option">
                              <i :class="option.icon"></i>
                              <span>{{ option.name }}</span>
                            </div>
                          </template>
                        </multi-select>
                        <input-error :message="form.errors.id_channel" />
                      </div>

                      <div class="col-12 mb-2">
                        <label class="form-label small fw-bold text-muted"
                          >PRODUK YANG DIMINATI
                          <span class="text-danger">*</span>
                        </label>
                        <multi-select
                          :class="{ 'is-invalid': form.errors.id_product }"
                          :multiple="true"
                          placeholder="Ketik minimal 2 huruf..."
                          v-model="productSelect.selected"
                          :options="productSelect.options"
                          :loading="productSelect.loading"
                          track-by="id_product"
                          label="name"
                          :internal-search="false"
                          :clear-on-select="false"
                          :close-on-select="false"
                          :searchable="true"
                          @search-change="productSelect.search"
                          @update:model-value="productSelect.onSelect"
                          :show-labels="false"
                          open-direction="bottom"
                        >
                          <template #option="{ option }">
                            <div class="d-flex align-items-center">
                              <img
                                :src="imageSource(option)"
                                width="45"
                                height="45"
                                class="rounded me-2"
                                style="object-fit: cover"
                              />

                              <div class="fw-semibold mb-2">
                                {{ option.name }}
                              </div>
                            </div>
                          </template>
                          <template #singleLabel="{ option }">
                            <div class="d-flex align-items-center">
                              <img
                                :src="imageSource(option)"
                                width="35"
                                height="35"
                                class="rounded me-2"
                                style="object-fit: cover"
                              />

                              {{ option.name }}
                            </div>
                          </template>
                        </multi-select>
                        <input-error :message="form.errors.id_product" />
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="p-3 border rounded-3 bg-white shadow-sm position-relative">
                    <h6
                      class="form-label fw-bold text-primary mb-3 border-bottom pb-2"
                      style="font-size: 13px"
                    >
                      <i class="bi bi-funnel-fill me-1"></i> 3. STATUS PENJUALAN
                    </h6>

                    <div class="row g-2">
                      <div class="col-md-6 mb-2">
                        <label class="form-label small fw-bold text-muted"
                          >SUHU KETERTARIKAN
                          <span class="text-danger">*</span>
                        </label>
                        <select-input
                          text="-- Pilih Suhu --"
                          name="temp"
                          v-model="form.temperature"
                          :error="form.errors.temperature"
                          :selectClass="{
                            'text-danger fw-bold': form.temperature === 'COLD',
                            'text-warning fw-bold': form.temperature === 'WARM',
                            'text-success fw-bold': form.temperature === 'HOT',
                          }"
                          :options="tempOptions"
                        />
                        <input-error :message="form.errors.temperature" />
                      </div>

                      <div class="col-md-6 mb-2">
                        <label class="form-label small fw-bold text-muted"
                          >STATUS PROSPEK
                          <span class="text-danger">*</span>
                        </label>

                        <select-input
                          text="-- Pilih Status --"
                          v-model="form.status"
                          name="status"
                          :error="form.errors.status"
                          :options="prospectOptions"
                        />
                        <input-error :message="form.errors.status" />
                      </div>
                      <div class="col-12 mt-2">
                        <label class="form-label small fw-bold text-muted"
                          >CATATAN
                          <span class="fst-italic">(Optional)</span>
                        </label>
                        <text-area
                          :error="form.errors.notes"
                          name="notes"
                          v-model="form.notes"
                          :rows="2"
                          placeholder="Contoh: Buat catatan jika ada..."
                        />
                        <input-error :message="form.errors.notes" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-show="activeTab === 'convert_lead'" class="row g-3 animate-fade-in">
                <div v-if="isLocked" class="col-12">
                  <div class="alert alert-info border-0 shadow-sm p-3">
                    <i class="bi bi-lock-fill me-2"></i>
                    <strong>Data Transaksi Terkunci!</strong><br />
                    <small
                      >Data ini sudah sah menjadi penjualan. Perubahan data keuangan harus
                      melalui menu Penjualan/Invoice.</small
                    >
                  </div>
                </div>

                <div class="col-md-4">
                  <label
                    for="deal_price_input"
                    class="form-label small fw-bold text-muted"
                    >HARGA DEAL*</label
                  >
                  <input-currency
                    :disabled="isLocked"
                    inputClass="text-success"
                    :decimals="0"
                    name="deal_price"
                    :error="form.errors.deal_price"
                    v-model="form.deal_price"
                    placeholder="0"
                  />
                  <input-error :message="form.errors.deal_price" />
                  <small class="text-muted d-block mt-1"
                    >Terbilang:
                    <strong>{{ currencyFormat(form.deal_price) }}</strong></small
                  >
                </div>

                <div class="col-md-4">
                  <label
                    for="paid_amount_input"
                    class="form-label small fw-bold text-muted"
                    >UANG MASUK SEKARANG *</label
                  >
                  <input-currency
                    :disabled="isLocked"
                    :readonly="form.payment_status === 'LUNAS'"
                    :error="form.errors.paid_amount"
                    :decimals="0"
                    name="paid_amount"
                    v-model="form.paid_amount"
                    placeholder="0"
                  />
                  <input-error :message="form.errors.paid_amount" />
                  <small
                    v-if="form.deal_price - form.paid_amount > 0"
                    class="text-danger mt-1 d-block fw-medium"
                    style="font-size: 11px"
                  >
                    Sisa Tagihan: {{ currencyFormat(dpAmount) }}
                  </small>
                </div>
                <div class="col-md-4">
                  <label for="quantity_input" class="form-label small fw-bold text-muted"
                    >QUANTITY *</label
                  >
                  <input-number
                    :disabled="isLocked"
                    :maxChar="5"
                    :error="form.errors.quantity"
                    name="quantity"
                    v-model="form.quantity"
                    placeholder="0"
                  />
                  <input-error :message="form.errors.quantity" />
                </div>

                <div class="col-md-6">
                  <label
                    for="payment_status_select"
                    class="form-label small fw-bold text-muted"
                    >STATUS BAYAR *</label
                  >
                  <select-input
                    :disabled="isLocked"
                    name="payment_status"
                    v-model="form.payment_status"
                    :error="form.errors.payment_status"
                    :options="[
                      { label: '✅ LUNAS Penuh', value: 'LUNAS' },
                      { label: '⏳ Uang Muka (DP)', value: 'DP' },
                    ]"
                  />
                  <input-error :message="form.errors.payment_status" />
                </div>

                <div class="col-md-6">
                  <label
                    for="payment_method_select"
                    class="form-label small fw-bold text-muted"
                    >METODE PEMBAYARAN *</label
                  >
                  <select-input
                    :disabled="isLocked"
                    name="payment_method"
                    v-model="form.payment_method"
                    :error="form.errors.payment_method"
                    :options="[
                      { label: '🏦 Tunai / Cash', value: 'cash' },
                      { label: '🏦 Transfer', value: 'transfer' },
                      { label: '📱 Debit', value: 'debit' },
                      { label: '💵 QRIS', value: 'qris' },
                    ]"
                  />
                  <input-error :message="form.errors.payment_method" />
                </div>

                <div
                  v-if="
                    form.payment_method === 'transfer' || form.payment_method === 'debit'
                  "
                  class="col-12"
                >
                  <label class="form-label small fw-bold text-muted mb-2"
                    >NAMA BANK*</label
                  >
                  <input-text
                    name="bank_name"
                    v-model="form.bank_name"
                    :error="form.errors.bank_name"
                    placeholder="Cth: BCA, Mandiri, BNI Dll"
                  />
                  <input-error :message="form.errors.bank_name" />
                </div>

                <div class="col-12 mt-3">
                  <label
                    for="notes_sales_input"
                    class="form-label small fw-bold text-muted"
                    >CATATAN TRANSAKSI <small class="fst-italic">(Opsional)</small></label
                  >
                  <text-area
                    :disabled="isLocked"
                    :maxChar="500"
                    :error="form.errors.notes_sales"
                    name="notes_sales"
                    v-model="form.notes_sales"
                    :rows="2"
                    placeholder="Buat catatan tambahan di sini jika ada..."
                  />
                  <input-error :message="form.errors.notes_sales" />
                </div>
              </div>
            </form-wrapper>
          </div>
        </template>

        <template #footer>
          <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
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
              :class="isEdit ? 'btn-c-success' : 'btn-c-primary'"
            >
              <span
                v-if="form.processing"
                class="spinner-border spinner-border-sm me-2"
              ></span>
              <i
                v-else
                :class="isEdit ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"
              ></i>
              {{ submitTextButton }}
            </button>
          </div>
        </template>
      </modal>
    </div>
  </div>
</template>
<style scoped>
/* EFEK MUNCUL HALUS UNTUK KONTEN TAB */
.animate-fade-in {
  animation: fadeInSlideUp 0.35s ease-out forwards;
}

@keyframes fadeInSlideUp {
  from {
    opacity: 0;
    transform: translateY(-15px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.timeline-line {
  position: absolute;
  left: 16px;
  top: 34px;
  bottom: -20px;
  width: 2px;
  background-color: #cbd5e1;
  z-index: 1;
}

/* DESAIN SWITCHER TABS MEWAH ALA APPLE / LINEAR */
.celestial-tab-container {
  display: flex;
  background-color: #f1f5f9;
  /* Slate-100 */
  padding: 5px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.celestial-tab-btn {
  flex: 1;
  border: none;
  background: transparent;
  padding: 10px 15px;
  border-radius: 9px;
  font-size: 0.885rem;
  font-weight: 600;
  color: #64748b;
  /* Slate-500 */
  transition: all 0.2s ease-in-out;
  display: flex;
  align-items: center;
  justify-content: center;
}

.celestial-tab-btn:hover {
  color: #1e293b;
}

.celestial-tab-btn.active {
  background-color: #ffffff;
  color: var(--c-primary);
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.06);
}
</style>
