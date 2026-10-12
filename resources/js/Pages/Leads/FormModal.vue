<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { hasRole } from "@/Composables/useAuth";
import { useRowAlert } from "@/Composables/useAlert";
import { currencyFormat } from "@/Helpers/currencyFormat";
import { formatDate } from "@/Helpers/formatDate";
import { formatForDatetimeLocal } from "@/Helpers/formatForDatetimeLocal";
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

const form = useForm({
  name: "",
  phone: "",
  email: "",
  id_channel: null,
  temperature: null,
  customer_type: null,
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
    name: data.name,
    phone: data.phone,
    email: data.email,
    id_channel: data.channel?.id_channel,
    temperature: data.temperature,
    customer_type: data.customer_type,

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

  if (data.products && data.products.length > 0) {
    selectedProductObj.value = data.products?.map((p) => ({
      id_product: p.id_product,
      name: p.product_name || "Produk Terpilih",
      image_path: p.image_path || "",
      image_link: p.image_link || "",
    }));
    productOptions.value = [...selectedProductObj.value];
  } else {
    selectedProductObj.value = [];
    productOptions.value = [];
  }
  dataLead.value = data;
  form.reset();
};
const resetForm = () => {
  form.defaults({
    name: "",
    phone: "",
    email: "",
    id_channel: null,
    temperature: null,
    customer_type: null,
    status: null,
    notes: "",
    id_product: [],
    product_name: [],

    deal_price: 0,
    paid_amount: 0,
    payment_status: "LUNAS",
    payment_method: "cash",
    quantity: "",
    bank_name: "",
    notes_sales: "",
  });
  form.reset();
  form.clearErrors();
  dataLead.value = null;
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
    const { data } = await axios.get(`/leads/show/${props.idLead}`);
    const cleanData = data.data;
    fillForm(cleanData);
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
        "name",
        "phone",
        "email",
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
  selectedProductObj.value = null;
  productOptions.value = [];
};
const submitTextButton = computed(() => {
  if (form.processing) return "Memproses...";
  if (form.status === "CLOSED_WON" && form.temperature === "HOT") return "Proses Closing";
  return isEdit.value ? "Simpan Perubahan" : "Simpan Prospek";
});

const updateActivity = () => {
  router.put(
    route("leads.activity.update", activityEdit.id),
    {
      description: activityEdit.new_value,
    },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        const activity = dataLead.value.activity.find(
          (actv) => actv.id_lead_activity === activityEdit.id
        );
        if (activity) {
          activity.description = activityEdit.new_value;
        }
        show({
          id: activityEdit.id,
          message: "Aktivitas berhasil diperbarui",
          type: "success",
          duration: 5000,
        });
        cancelEditActivity();
      },
      onError: (errors) => {
        show({
          id: activityEdit.id,
          message: errors.description,
          type: "danger",
          duration: 5000,
        });
      },
    }
  );
};

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
const productOptions = ref([]);
const loadingProduct = ref(false);
const selectedProductObj = ref([]);
const searchProduct = debounce(async (query) => {
  if (query.length < 2) {
    productOptions.value = [];
    return;
  }

  loadingProduct.value = true;
  try {
    const { data } = await axios.get(route("product.search"), {
      params: { keyword: query },
    });
    productOptions.value = data;
    console.log(data);
  } catch (error) {
    console.error("Gagal mengambil data produk", error);
  } finally {
    loadingProduct.value = false;
  }
}, 400);

const onSelectProduct = (product) => {
  // form.id_product = product?.id_product ?? null;
  // form.product_name = product?.name ?? null;

  if (product && product.length > 0) {
    form.id_product = product.map((p) => p.id_product);
    form.product_name = product.map((p) => p.name);
  } else {
    form.id_product = [];
    form.product_name = [];
  }
};
const onRemoveProduct = (removedProduct) => {
  // form.id_product = null;
  // form.product_name = null;

  if (removedProduct && removedProduct.id_product) {
    form.id_product = form.id_product.filter((id) => id !== removedProduct.id_product);
    form.product_name = form.product_name.filter((name) => name !== removedProduct.name);
  }
};
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
                    <div class="row g-2">
                      <div class="col-md-6 mb-2">
                        <label class="form-label small fw-bold text-muted"
                          >NAMA PROSPEK
                          <span class="text-danger">*</span>
                        </label>
                        <input-text
                          :error="form.errors.name"
                          v-model="form.name"
                          name="name"
                          placeholder="Cth: Budi Santoso"
                        />
                        <input-error :message="form.errors.name" />
                      </div>

                      <div class="col-md-6 mb-2">
                        <label class="form-label small fw-bold text-muted"
                          >NO. HANDPHONE / WA
                          <span class="text-danger">*</span>
                        </label>
                        <input-number
                          :maxChar="15"
                          :error="form.errors.phone"
                          v-model="form.phone"
                          name="phone"
                          placeholder="081233445566"
                        />
                        <input-error :message="form.errors.phone" />
                      </div>

                      <div class="col-md-6 mb-2">
                        <label class="form-label small fw-bold text-muted"
                          >EMAIL PELANGGAN (Opsional)</label
                        >
                        <input-text
                          :error="form.errors.email"
                          v-model="form.email"
                          name="email"
                          placeholder="Cth: dimas@perusahaan.com"
                        />
                        <input-error :message="form.errors.email" />
                      </div>

                      <div class="col-md-6 mb-2">
                        <label class="form-label small fw-bold text-muted"
                          >TIPE PELANGGAN
                          <span class="text-danger">*</span>
                        </label>
                        <select-input
                          text="--Pilih Tipe Pelanggan--"
                          :error="form.errors.customer_type"
                          name="customer_type"
                          v-model="form.customer_type"
                          :options="[
                            { value: 'NEW_CUSTOMER', label: '🌱 Pelanggan Baru' },
                            {
                              value: 'REPEAT_ORDER',
                              label: '🔁 Pelanggan Lama / Beli Ulang',
                            },
                            { value: 'WIN_BACK', label: '♻️ Reaktivasi / Pernah Cancel' },
                          ]"
                        />
                        <input-error :message="form.errors.customer_type" />
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
                          v-model="selectedProductObj"
                          :options="productOptions"
                          :loading="loadingProduct"
                          track-by="id_product"
                          label="name"
                          :internal-search="false"
                          :clear-on-select="false"
                          :close-on-select="false"
                          :searchable="true"
                          @search-change="searchProduct"
                          @update:model-value="onSelectProduct"
                          @remove="onRemoveProduct"
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
                    >CATATAN TRANSAKSI (Opsional)</label
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
