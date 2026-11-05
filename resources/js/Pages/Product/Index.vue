<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { useFilter } from "@/Composables/useFilter";
import { hasRole, hasPermission, canAccess } from "@/Composables/useAuth";
import { computed, ref } from "vue";
import { formatDate } from "@/Helpers/formatDate";
import { highlight } from "@/Helpers/Highlight";
import { useConfirm } from "@/Composables/useConfirm";
import FormModal from "./FormModal.vue";
import ImagePreviewModal from "./ImagePreviewModal.vue";
const confirm = useConfirm();
const props = defineProps({
  products: Object,
  category: Array,
  filters: Object,
});
const { filters, isLoading } = useFilter({
  routeName: "product.index",
  initialFilters: {
    keyword: props.filters.keyword ?? "",
    limit: props.filters.limit ?? 10,
    order_by: props.filters.order_by ?? "latest",
  },
  watchFields: ["keyword", "limit", "order_by"],
  only: ["products", "filters"],
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
    label: "Kode & Sumber",
    key: "code_",
    attrs: {
      class: "text-center",
    },
  },
  {
    label: "Gambar",
    key: "images",
    attrs: {
      class: "text-center",
    },
  },
  {
    label: "Info Produk",
    key: "product",
  },
  {
    label: "Harga Saat Ini (Official)",
    key: "price",
    attrs: {
      class: "text-center",
    },
  },
  {
    label: "Status",
    key: "status_",
    attrs: {
      class: "text-center",
    },
  },
  {
    label: "Dibuat Oleh",
    key: "creator",
    attrs: {
      class: "text-start",
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
const selectedId = ref(null);
const showImagePreview = ref(false);
const selectedImage = ref(null);
const openCreateModal = () => {
  showModal.value = true;
  selectedId.value = null;
};
const openEditModal = (data) => {
  showModal.value = true;
  selectedId.value = data.id_product;
};
const openImagePreview = (data) => {
  showImagePreview.value = true;
  selectedImage.value = data;
};
const deleted = async (nameRoute, data) => {
  const setConfirm = await confirm.ask({
    title: `Hapus Produk ${data.product_name} ?`,
    message: "Data yang terhapus tidak dapat dikembalikan.",
    confirmText: "Ya, Hapus",
    variant: "danger",
  });

  if (setConfirm) {
    router.delete(route(nameRoute, data.id_product), {
      preserveScroll: true,
      replace: true,
      preserveState: true,
    });
  }
};
const getImageUrl = (item) => {
  if (item.source === "scrape" && item.image_link) {
    return item.image_link; // Gambar dari URL luar
  }
  if (item.source === "manual" && item.image_path) {
    return `/storage/${item.image_path}`; // Gambar lokal dari storage Laravel
  }
  return "/storage/no_image/no-image.png"; // Fallback jika tidak ada gambar
};
const formatCategory = (category) => {
  return category
    .split("/")
    .map((part) => part.replace(/-/g, " "))
    .map((part) => part.replace(/\b\w/g, (char) => char.toUpperCase()))
    .join(" - ");
};

const formatRoleLabel = (roleName) => {
  if (!roleName) return "";
  if (typeof getRoleName === "function") {
    return getRoleName(roleName)?.label ?? roleName;
  }
  return roleName.replace(/_/g, " ");
};
const getRoleName = (role) => {
  const map = {
    super_admin: {
      label: "VVIP",
    },
    supervisor: {
      label: "SPV",
    },
    sales: {
      label: "SLS",
    },
    admin: {
      label: "ADM",
    },
    teknisi: {
      label: "TKN",
    },
  };
  return map[role] || map.supervisor;
};
</script>
<template>
  <Head title="Daftar Produk" />
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
                  <h5 class="fw-bold mb-1">Daftar Produk</h5>
                  <!-- <small class="text-muted">Semua produk yang terdaftar</small> -->
                </div>
                <button
                  v-if="canAccess('product-create')"
                  @click.prevent="openCreateModal"
                  class="btn btn-c-primary px-4 py-2"
                >
                  <i class="bi bi-plus-circle me-1"></i> Produk Baru
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
                    placeholder="Cari produk dan kode produk...."
                  />
                </div>
              </div>
              <div class="col-xl-2 col-6 col-md-6 col-lg-6">
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
              <div class="col-xl-2 col-6 col-md-6 col-lg-6">
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
                :items="products?.data"
                rowKey="id_product"
              >
                <template #empty>
                  <div class="text-center py-5">
                    <i class="bi bi-funnel fa-3x text-muted opacity-25 mb-3 d-block"></i>
                    <h6 class="text-muted fw-bold">Data produk Masih Kosong</h6>
                    <p class="text-muted small mb-0">
                      Klik tombol "Buat produk baru" di pojok kanan atas untuk membuat
                      produk.
                    </p>
                  </div>
                </template>

                <template #row="{ item, index }">
                  <td class="td-number">
                    {{ index + 1 + (products?.current_page - 1) * products?.per_page }}
                  </td>

                  <td class="text-center align-middle py-2">
                    <div class="d-flex flex-column align-items-center gap-2">
                      <span
                        class="badge bg-light text-dark border border-secondary border-opacity-25 font-monospace fw-semibold px-2 py-1 rounded-2"
                        style="font-size: 14px"
                      >
                        <i class="bi bi-upc-scan text-primary me-1"></i>
                        <span
                          v-html="highlight(item.code || '-', filters.keyword)"
                        ></span>
                      </span>

                      <!-- Sumber Data (Scrape vs Manual) -->
                      <span
                        v-if="item.source"
                        class="badge rounded-pill px-2 py-1 fw-medium text-capitalize"
                        :class="
                          item.source === 'scrape'
                            ? 'bg-info-subtle text-info border border-info border-opacity-25'
                            : 'bg-secondary-subtle text-secondary border border-secondary border-opacity-25'
                        "
                        style="font-size: 12px"
                      >
                        <i
                          class="bi me-1"
                          :class="
                            item.source === 'scrape'
                              ? 'bi-cloud-arrow-down-fill'
                              : 'bi-pencil-fill'
                          "
                        ></i>
                        {{ item.source === "scrape" ? "Web Scrape" : "Input Manual" }}
                      </span>
                    </div>
                  </td>

                  <td class="text-center align-middle py-2">
                    <div class="d-inline-block position-relative">
                      <img
                        v-if="item.image_link || item.image_path"
                        :src="getImageUrl(item)"
                        class="product-thumbnail shadow-sm border"
                        width="80"
                        height="80"
                        loading="lazy"
                        decoding="async"
                        :alt="item.product_name"
                        :title="`Klik untuk memperbesar: ${item.product_name}`"
                        @click.prevent="openImagePreview(item)"
                      />
                      <div
                        v-else
                        class="product-thumbnail-empty bg-light border rounded-3 d-flex flex-column align-items-center justify-content-center text-muted mx-auto"
                      >
                        <i class="bi bi-image fs-1 opacity-50"></i>
                        <span style="font-size: 10px">No Image</span>
                      </div>
                    </div>
                  </td>

                  <td
                    class="align-middle py-2"
                    style="min-width: 260px; max-width: 460px"
                  >
                    <div
                      class="fw-bold text-dark mb-2 lh-sm text-truncate"
                      style="font-size: 14px"
                      :title="item.product_name"
                      v-html="highlight(item.product_name, filters.keyword)"
                    ></div>

                    <!-- Kategori & Kondisi -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                      <span
                        class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-1 fw-medium"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-tag-fill me-1 opacity-75"></i>
                        {{ formatCategory(item.category) }}
                      </span>

                      <span
                        v-if="item.condition"
                        class="badge rounded-pill px-2 py-1 fw-semibold text-capitalize"
                        :class="
                          item.condition === 'new'
                            ? 'bg-primary-subtle text-primary border border-primary border-opacity-25'
                            : 'bg-warning-subtle text-warning-emphasis border border-warning border-opacity-25'
                        "
                        style="font-size: 12px"
                      >
                        <i class="bi bi-stars me-1" v-if="item.condition === 'new'"></i>
                        {{ item.condition === "new" ? "Baru (New)" : item.condition }}
                      </span>
                    </div>

                    <!-- Link Produk Eksternal -->
                    <div>
                      <a
                        v-if="item.link"
                        :href="item.link"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="badge bg-white text-primary border border-primary border-opacity-25 rounded-pill px-2 py-1 text-decoration-none d-inline-flex align-items-center gap-1 shadow-sm hover-link"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-box-arrow-up-right"></i>
                        <span>Lihat di Website</span>
                      </a>
                      <span
                        v-else
                        class="text-muted fst-italic d-inline-flex align-items-center"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-link-45deg me-1 opacity-50"></i> Link tidak
                        tersedia
                      </span>
                    </div>
                  </td>

                  <!-- 5. Harga Saat Ini, Tipe Harga & Periode Promo -->
                  <td class="text-center align-middle py-2" style="min-width: 190px">
                    <div v-if="Number(item.base_price) > 0">
                      <!-- Badge Tipe Harga (Promo / Normal) -->
                      <div class="mb-1" v-if="item.price_type">
                        <span
                          class="badge rounded-pill px-2 py-1 text-uppercase"
                          :class="
                            item.price_type !== 'normal' ||
                            Number(item.discount_price) > 0
                              ? 'bg-danger-subtle text-danger border border-danger border-opacity-25'
                              : 'bg-light text-secondary border'
                          "
                          style="font-size: 11px; letter-spacing: 0.4px"
                        >
                          {{ item.price_type }}
                        </span>
                      </div>

                      <!-- Jika Ada Harga Diskon -->
                      <div v-if="Number(item.discount_price) > 0">
                        <div class="fw-bold text-success" style="font-size: 14px">
                          Rp {{ Number(item.discount_price).toLocaleString("id-ID") }}
                        </div>

                        <div
                          class="d-flex justify-content-center align-items-center gap-2 mt-1"
                        >
                          <small
                            class="text-muted text-decoration-line-through"
                            style="font-size: 12px"
                          >
                            Rp {{ Number(item.base_price).toLocaleString("id-ID") }}
                          </small>
                          <span
                            v-if="Number(item.discount_percentage) > 0"
                            class="badge bg-danger rounded-pill px-2 py-1"
                            style="font-size: 11px"
                          >
                            -{{ item.discount_percentage }}%
                          </span>
                        </div>
                      </div>

                      <!-- Jika Harga Normal Tanpa Diskon -->
                      <div v-else class="fw-bold text-dark" style="font-size: 14px">
                        Rp {{ Number(item.base_price).toLocaleString("id-ID") }}
                      </div>

                      <!-- Periode Berlaku (Jika ada valid_from / valid_until) -->
                      <div
                        v-if="item.valid_from || item.valid_until"
                        class="text-muted mt-1"
                        style="font-size: 12px"
                      >
                        <i class="bi bi-calendar-range me-1"></i>
                        {{
                          item.valid_until
                            ? `s/d ${formatDate(item.valid_until)}`
                            : formatDate(item.valid_from)
                        }}
                      </div>
                    </div>

                    <div
                      v-else
                      class="d-inline-flex align-items-center gap-1 px-2 py-1 bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-3"
                      style="font-size: 12px"
                    >
                      <i class="bi bi-exclamation-triangle-fill"></i>
                      <span>Harga belum diset</span>
                    </div>
                  </td>

                  <td class="text-center align-middle py-2">
                    <span
                      v-if="item.status === 'published'"
                      class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-semibold"
                      style="font-size: 12px"
                    >
                      <i class="bi bi-check-circle-fill me-1"></i> Published
                    </span>
                    <span
                      v-else
                      class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill fw-semibold text-capitalize"
                      style="font-size: 12px"
                    >
                      <i class="bi bi-archive-fill me-1"></i> {{ item.status || "Draft" }}
                    </span>
                  </td>

                  <td class="align-middle text-start py-2" style="min-width: 200px">
                    <div class="d-flex align-items-start">
                      <div
                        class="client-avatar me-2 bg-gradient-light border text-primary fw-bold small d-flex align-items-center justify-content-center shadow-sm flex-shrink-0 mt-1"
                      >
                        {{
                          item.creator_name
                            ? item.creator_name.substring(0, 2).toUpperCase()
                            : "NA"
                        }}
                      </div>

                      <div class="d-flex flex-column small" style="line-height: 1.35">
                        <!-- Nama Creator & Badge Role -->
                        <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
                          <span class="fw-bold text-dark text-capitalize">
                            {{ item.creator_name || "Sistem" }}
                          </span>
                          <span
                            v-if="item.creator_role_name"
                            class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded-pill px-2 py-0 fw-medium text-capitalize"
                            style="font-size: 10px"
                          >
                            {{ formatRoleLabel(item.creator_role_name) }}
                          </span>
                        </div>

                        <!-- Waktu Dibuat & Diubah -->
                        <div
                          class="text-muted d-flex flex-column gap-1"
                          style="font-size: 11px"
                        >
                          <span>
                            <i class="bi bi-plus-circle me-1 opacity-75"></i>
                            {{ formatDate(item.created_at) }}
                          </span>

                          <span
                            v-if="item.updated_at && item.updated_at !== item.created_at"
                            class="text-muted"
                          >
                            <i class="bi bi-pencil-square me-1 text-warning"></i>
                            {{ formatDate(item.updated_at) }}
                            <template
                              v-if="
                                item.updated_by &&
                                item.updated_by !== item.created_by &&
                                item.updater_name
                              "
                            >
                              oleh
                              <strong class="text-dark text-capitalize">{{
                                item.updater_name
                              }}</strong>
                              <span v-if="item.updater_role_name" class="opacity-75">
                                ({{ formatRoleLabel(item.updater_role_name) }})
                              </span>
                            </template>
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>

                  <td class="text-center align-middle py-2">
                    <div
                      class="d-flex justify-content-center gap-1"
                      v-if="
                        canAccess('product-edit', item) ||
                        canAccess('product-delete', item)
                      "
                    >
                      <button
                        v-if="canAccess('product-edit', item)"
                        @click.prevent="openEditModal(item)"
                        type="button"
                        class="btn btn-c-slate"
                        title="Edit Produk"
                      >
                        <i class="bi bi-pencil-square"></i>
                      </button>
                      <button
                        v-if="canAccess('product-delete', item)"
                        @click.prevent="deleted('product.delete', item)"
                        type="button"
                        class="btn btn-c-danger"
                        title="Hapus Produk"
                      >
                        <i class="bi bi-trash"></i>
                      </button>
                    </div>
                    <div v-else class="d-flex justify-content-center">
                      <span
                        class="small fw-semibold badge badge-soft-info rounded-5 d-inline-flex align-items-center gap-1"
                      >
                        <i class="bi bi-lock-fill"></i>
                        No Action
                      </span>
                    </div>
                  </td>
                </template>

                <template #pagination>
                  <div
                    v-if="products?.data.length > 0"
                    class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center"
                  >
                    <small class="text-muted mb-2 mb-md-0">
                      Menampilkan <strong>{{ products.from ?? 0 }}</strong> -
                      <strong>{{ products.to ?? 0 }}</strong> dari
                      <strong>{{ products.total ?? 0 }}</strong>
                    </small>
                    <pagination
                      size="pagination-sm"
                      :links="products?.links"
                      routeName="product.index"
                      :additionalQuery="{
                        keyword: filters.keyword,
                        limit: filters.limit,
                        order_by: filters.order_by,
                      }"
                      :only="['products', 'filters']"
                    />
                  </div>
                </template>
              </table-wrapper>
            </div>
          </div>
        </div>
      </div>
      <FormModal
        :category="props.category"
        :idProduct="selectedId"
        :show="showModal"
        @update:show="showModal = $event"
      />
      <!-- Image Preview -->
      <ImagePreviewModal
        :image="selectedImage"
        :show="showImagePreview"
        @update:show="showImagePreview = $event"
      />
    </template>
  </app-layout>
</template>
<style scoped>
.product-thumbnail {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 10px;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-thumbnail:hover {
  transform: scale(1.08);
  border-color: #87b4f7 !important;
  box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15) !important;
}

.product-thumbnail-empty {
  width: 80px;
  height: 80px;
}

.hover-link:hover {
  background-color: #0d6efd !important;
  color: #ffffff !important;
}
</style>
