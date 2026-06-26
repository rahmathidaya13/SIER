<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import $, { error } from 'jquery';
import 'select2';
import 'select2/dist/css/select2.css';
import 'select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css'
// 1. Props dan Emit
const props = defineProps({
    modelValue: [String, Number, Array], // Untuk v-model
    options: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    name: {
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    isValid: {
        type: Boolean,
        default: true,
    },
    isInvalid: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();
const emit = defineEmits(['update:modelValue', 'change', 'select']);

// 2. Referensi DOM
const selectElement = ref(null);
let $select = null; // Variabel untuk menyimpan instance jQuery Select2
// 3. Inisialisasi Select2
onMounted(async () => {
    await nextTick()
    $select = $(selectElement.value);  // Inisialisasi jQuery dan Select2
    // Apply options/data
    if (props.options.length) {
        // Select2 memiliki cara sendiri untuk memuat data jika diperlukan
        $select.html(''); // Bersihkan slot jika ada
        props.options.forEach(option => {
            // Pastikan format opsi Select2 (id, text)
            const data = { id: option.id, text: option.text || option.name };
            const newOption = new Option(data.text, data.id, false, false);
            $select.append(newOption);
        });
    }

    // Inisialisasi Select2 dengan pengaturan (settings)
    $select.select2({
        theme: 'bootstrap-5',
        dropdownParent: $(selectElement.value).parent(),
        ...props.settings,
    });
    // Set nilai awal
    $select.val(props.modelValue).trigger('change');

    // 4. Sinkronisasi Nilai Select2 ke Vue (v-model)
    $select.on('change', function () {
        const value = $select.val();
        emit('update:modelValue', value);
        emit('change', value);

    });

    // Menangkap data UTUH saat item dipilih
    $select.on('select2:select', function (e) {
        // e.params.data berisi semua properti dari proses AJAX (id, text, product_name, dll)
        emit('select', e.params.data);
    });

    // Menangkap event saat item dihapus (tombol X di klik)
    $select.on('select2:clear', function (e) {
        emit('select', null);
    });
});

// 5. Watcher untuk Sinkronisasi Nilai Vue ke Select2
watch(() => props.modelValue, (newValue) => {
    // Hanya update Select2 jika nilainya berbeda,
    // untuk mencegah loop tak terbatas (infinite loop)
    if (newValue !== $select.val()) {
        $select.val(newValue).trigger('change');
    }
});


// 6. Cleanup saat Komponen Dihancurkan
onUnmounted(() => {
    if ($select && $select.data('select2')) {
        $select.off('change'); // Hapus event listener
        $select.off('select2:select'); // Hapus event baru
        $select.off('select2:clear');  // Hapus event baru
        $select.select2('destroy'); // Hancurkan instance Select2
    }
});

const hasError = computed(() => {
    return page.props.errors && page.props.errors[props.name];
});
</script>

<template>
    <select :class="[{
        'is-invalid': props.isInvalid && props.error,
        'is-valid': props.isValid && modelValue && !props.error
    }]" class="select2-hidden-accessible form-select" ref="selectElement" :id="props.name + '_select'"
        :name="props.name">
        <slot></slot>
    </select>
</template>
