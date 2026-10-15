<script setup>
import { Head, router, Link } from "@inertiajs/vue3";
import { computed, ref, inject } from "vue";
import { useFilter } from "@/Composables/useFilter";
import { useNotification } from "@/Composables/useNotification";
import { hasRole, hasPermission } from "@/Composables/useAuth";
import { formatDate } from "@/Helpers/formatDate";

const props = defineProps({
  notifications: Object,
  filters: Object,
});

const selectedRow = ref([]);

const { unreadCount, setUnreadCount, resetUnread, decrementUnread } = useNotification();

const { filters, isLoading } = useFilter({
  routeName: "notification.index",
  initialFilters: {
    status: props.filters.status ?? "unread",
    limit: props.filters.limit ?? 10,
    order_by: props.filters.order_by ?? "latest",
  },
  watchFields: ["status", "limit", "order_by"],
  only: ["notifications", "filters"],
  debounceTime: 500,
});
const hasUnreadInPage = computed(() => {
  if (!props.notifications?.data) return false;
  return props.notifications.data.some((item) => item.read_at === null);
});

// Fungsi untuk klik "Tandai Semua"
const markSelectedAsRead = () => {
  if (!selectedRow.value.length) {
    alert("Pilih item terlebih dahulu");
    return;
  }
  const ids = selectedRow.value.map((item) => item.id);
  resetUnread();
  router.post(
    route("notifications.readAll"),
    { ids: ids },
    {
      preserveScroll: true,
      replace: true,
      onSuccess: () => {
        selectedRow.value = [];
        resetUnread();
      },
      onError: () => {
        alert("Gagal menandai notifikasi sebagai dibaca. Silakan coba lagi.");
      },
    }
  );
};

// Fungsi untuk klik per item
const markAsRead = (notification) => {
  // Jika sudah dibaca, tidak perlu panggil backend lagi
  if (notification.read_at !== null) return;
  // decrementUnread();
  router.post(
    route("notifications.read", notification.id),
    {},
    {
      preserveScroll: true,
      replace: true,
      onSuccess: () => {
        // Jika berhasil, update read_at di frontend
        selectedRow.value = [];
        notification.read_at = new Date().toISOString();
        decrementUnread();
      },
      onError: () => {
        alert("Gagal menandai notifikasi sebagai dibaca. Silakan coba lagi.");
      },
    }
  );
};
const header = [
  {
    label: "No",
    key: "__index",
    attrs: {
      class: "text-center td-number",
    },
  },
  {
    label: "Title",
    key: "title",
    attrs: {
      style: {
        width: "25%",
      },
      class: "text-start",
    },
  },
  {
    label: "Message",
    key: "message",
    attrs: {
      style: {
        width: "25%",
      },
      class: "text-start",
    },
  },
  {
    label: "Action",
    key: "action",
    attrs: {
      class: "text-center",
    },
  },
];
const rowClasses = (item) => ({
  "tr-highlight-info": item.read_at === null,
});
console.log(props.notifications.data);
</script>
<template>
  <Head title="Notifikasi" />
  <app-layout>
    <template #content>
      <div class="row g-3 view-section">
        <div class="col-12">
          <callout />

          <div class="card border-0 rounded-4 overflow-hidden">
            <div class="card-header p-4 bg-white">
              <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3"
              >
                <div>
                  <h5 class="fw-bold mb-1">Daftar Notifikasi</h5>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center p-3 flex-wrap">
              <div class="row g-1 flex-grow-1">
                <div class="col-2">
                  <select-input
                    selectClass="rounded-4"
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
                <div class="col-2">
                  <select-input
                    selectClass="rounded-4"
                    :isValid="false"
                    name="status"
                    v-model="filters.status"
                    text="Semua Status"
                    :options="[
                      { label: 'Semua Status', value: 'all' },
                      { label: 'Belum dibaca', value: 'unread' },
                      { label: 'Sudah dibaca', value: 'read' },
                    ]"
                  />
                </div>
                <div class="col-2">
                  <select-input
                    selectClass="rounded-4"
                    :isValid="false"
                    name="order_by"
                    v-model="filters.order_by"
                    :options="[
                      { label: '🔼 Terbaru', value: 'latest' },
                      { label: '🔽 Terlama', value: 'oldest' },
                    ]"
                  />
                </div>
              </div>
              <div>
                <button
                  :disabled="!hasUnreadInPage || !selectedRow.length"
                  type="button"
                  @click.prevent="markSelectedAsRead"
                  class="btn btn-sm btn-c-slate px-4 py-2 shadow-sm rounded-4"
                >
                  <i class="bi bi-check2-all me-1"></i> Tandai
                  {{ selectedRow.length }} Dibaca
                </button>
              </div>
            </div>

            <div class="card-body p-0">
              <table-wrapper
                v-model:selected="selectedRow"
                :loader="isLoading"
                :markAll="true"
                :rowClass="rowClasses"
                :headers="header"
                :items="notifications?.data"
                rowKey="id"
                :disableRowCheck="(item) => item.read_at !== null"
              >
                <template #empty>
                  <div class="text-center py-5">
                    <i
                      class="bi bi-bell-slash-fill fa-3x text-muted opacity-25 mb-3 d-block"
                    ></i>
                    <h6 class="text-muted fw-bold">Data Notifikasi Masih Kosong</h6>
                  </div>
                </template>

                <template #row="{ item, index }">
                  <td class="td-number">
                    {{
                      index +
                      1 +
                      (notifications?.current_page - 1) * notifications?.per_page
                    }}
                  </td>

                  <td>
                    <div class="text-muted fw-semibold">
                      {{ item.data.title }}
                    </div>
                  </td>

                  <td>
                    <div class="fw-bold d-flex flex-column">
                      {{ item.data.message }}
                      <small class="text-muted" style="font-size: 11px">
                        {{ formatDate(item.created_at) }}
                      </small>
                    </div>
                    <template v-if="hasRole(['developer', 'super_admin', 'supervisor'])">
                      <Link
                        :href="item.data.action?.route"
                        class="btn btn-link p-0 text-decoration-none"
                        v-if="item.data.action?.route"
                      >
                        <span class="small">
                          {{ item.data.action.label }}
                          <i
                            :class="item.data.action.icon || 'bi bi-box-arrow-up-right'"
                          ></i>
                        </span>
                      </Link>
                    </template>
                  </td>

                  <td class="text-center">
                    <button
                      v-if="!item.read_at"
                      @click.prevent="markAsRead(item)"
                      type="button"
                      class="btn btn-c-primary btn-sm"
                      title="Tandai Dibaca"
                    >
                      <i class="bi bi-check2-all me-1"></i> Tandai Dibaca
                    </button>
                    <span v-else class="text-success fw-semibold">
                      <i class="bi bi-check-circle"></i>
                      Sudah Dibaca
                    </span>
                  </td>
                </template>

                <template #pagination>
                  <div
                    class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center"
                  >
                    <small class="text-muted mb-2 mb-md-0">
                      Menampilkan <strong>{{ notifications.from ?? 0 }}</strong> -
                      <strong>{{ notifications.to ?? 0 }}</strong> dari
                      <strong>{{ notifications.total ?? 0 }}</strong>
                    </small>
                    <pagination
                      size="pagination-sm"
                      :links="notifications?.links"
                      routeName="notification.index"
                      :additionalQuery="{
                        status: filters.status,
                        limit: filters.limit,
                        order_by: filters.order_by,
                      }"
                      :only="['notifications', 'filters']"
                    />
                  </div>
                </template>
              </table-wrapper>
            </div>
          </div>
        </div>
      </div>
    </template>
  </app-layout>
</template>
