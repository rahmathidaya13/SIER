<script setup>
import { computed, ref, watch } from "vue";
const props = defineProps({
  headers: Array,
  items: Array,
  modelValue: {
    type: Array,
    default: () => [],
  },
  rowKey: {
    type: [String, Number, Object, Array, Function],
    default: () => "id",
  },
  rowClass: {
    type: Function,
    default: () => "",
  },
  markAll: {
    type: Boolean,
    default: false,
  },
  loader: {
    type: Boolean,
    default: false,
  },
  loaderText: {
    type: String,
    default: "",
  },
  disableRowCheck: {
    type: Function,
    default: () => false,
  },
});

const emit = defineEmits(["update:selected"]);

const selectedRow = ref([]);
const rows = computed(() => props.items ?? []);
const isCompact = computed(() => rows.value.length <= 3);

// Fungsi pembantu untuk mendapatkan ID baris
const getRowId = (row) => {
  return row[props.rowKey] ?? row.id;
};

// Fungsi untuk memeriksa apakah semua baris terpilih
const isAllSelected = computed(() => {
  const selectableRows = rows.value.filter((r) => !props.disableRowCheck(r));
  if (selectableRows.length === 0) return false;
  return selectableRows.every((r) => selectedRow.value.includes(getRowId(r)));
});

// Fungsi untuk memilih semua baris
const toggleAll = (evt) => {
  const isChecked = evt.target.checked;
  // Ambil semua ID yang ada di HALAMAN INI saja
  const currentPageIds = rows.value
    .filter((r) => !props.disableRowCheck(r))
    .map((r) => getRowId(r));
  // Copy array lama
  let newSelection = [...selectedRow.value];
  if (isChecked) {
    // Gabungkan ID lama + ID halaman ini (Set biar gak duplikat)
    newSelection = [...new Set([...newSelection, ...currentPageIds])];
  } else {
    // Hapus HANYA ID halaman ini, sisanya biarkan
    newSelection = newSelection.filter((id) => !currentPageIds.includes(id));
  }

  selectedRow.value = newSelection;
};

// Emit perubahan ke parent
watch(selectedRow, (val) => {
  const selectDataObject = rows.value.filter((item) => val.includes(getRowId(item)));
  emit("update:selected", selectDataObject);
});

// Jika props.modelValue berubah dari luar, sinkronkan dengan selectedRow secara otomatis
watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal && newVal.length === 0) {
      selectedRow.value = [];
    }
  },
  { deep: true }
);

// Bersihkan ceklis secara otomatis jika pindah halaman pagination
watch(
  () => props.items,
  () => {
    selectedRow.value = [];
  },
  { deep: true }
);
// Fungsi untuk menggabungkan class internal (selected) dengan class dari luar
const computeRowClass = (item) => {
  const externalClass = props.rowClass(item); // Panggil fungsi dari luar
  const isSelected = selectedRow.value.includes(getRowId(item));
  const isDeleted = !!item.deleted_at;
  return [
    externalClass,
    isDeleted ? "row-deleted" : "",
    isSelected && !isDeleted ? "row-selected" : "",
    "align-middle transition-all",
  ];
};
const visibleHeaders = computed(() =>
  props.headers.filter((col) => col.visible !== false)
);
</script>
<template>
  <div class="table-wrapper">
    <slot name="filter-action" />
    <div class="table-responsive custom-scroll">
      <table class="table table-hover align-middle">
        <thead class="sticky-top">
          <tr>
            <th v-if="markAll" class="text-center" style="width: 50px">
              <div class="form-check d-flex justify-content-center">
                <input
                  :disabled="!rows.length || rows.every((r) => disableRowCheck(r))"
                  type="checkbox"
                  class="form-check-input custom-checkbox cursor-pointer"
                  :checked="isAllSelected"
                  @change="toggleAll($event)"
                />
              </div>
            </th>
            <th
              class="align-middle"
              v-for="col in visibleHeaders"
              :key="col.key"
              v-bind="col.attrs"
              style="font-size: 0.95rem"
            >
              <slot name="header" :col="col">
                {{ col.label }}
              </slot>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loader">
            <td
              :colspan="visibleHeaders.length + (markAll ? 1 : 0)"
              class="text-center py-5"
              style="height: 300px"
            >
              <div class="spinner-border text-primary" style="width: 2rem; height: 2rem">
                <span class="visually-hidden">Loading...</span>
              </div>
              <div class="mt-2 text-muted small fw-semibold">
                {{ loaderText || "Memuat Data..." }}
              </div>
            </td>
          </tr>

          <tr v-else-if="!rows.length">
            <td
              :colspan="visibleHeaders.length + (markAll ? 1 : 0)"
              class="text-center py-5"
              style="height: 300px"
            >
              <slot name="empty">
                <p class="text-muted fw-semibold">Tidak ada data</p>
              </slot>
            </td>
          </tr>
          <template v-else>
            <tr
              v-for="(item, index) in rows"
              :key="getRowId(item)"
              :class="computeRowClass(item)"
            >
              <td v-if="markAll" class="text-center">
                <div class="form-check d-flex justify-content-center">
                  <input
                    :disabled="disableRowCheck(item)"
                    type="checkbox"
                    class="form-check-input custom-checkbox"
                    :value="getRowId(item)"
                    v-model="selectedRow"
                  />
                </div>
              </td>

              <slot name="row" :item="item" :index="index" :selected="selectedRow" />
            </tr>
          </template>
        </tbody>
        <tfoot v-if="$slots.footer" class="bg-light border-top">
          <slot
            name="footer"
            :headers="visibleHeaders.length + (markAll ? 1 : 0)"
            :items="rows"
          />
        </tfoot>
      </table>
    </div>
    <slot v-if="$slots.pagination" name="pagination" />
  </div>
</template>
<style scoped>
/* Custom Checkbox Size */
.custom-checkbox {
  width: 1.1em;
  height: 1.1em;
  cursor: pointer;
}

.custom-scroll {
  overflow: auto;
  max-height: 650px;
  scrollbar-gutter: stable;
  scroll-behavior: smooth;
}

/* Custom Scrollbar */
.custom-scroll::-webkit-scrollbar {
  height: 8px;
  width: 7px;
}

.custom-scroll::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scroll::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 10px;
}

.custom-scroll::-webkit-scrollbar-thumb:hover {
  background: #d1d7e4;
}

table td {
  vertical-align: middle;
}
</style>
