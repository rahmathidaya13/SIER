<script setup>
import { ref, watch } from 'vue';

// Menggunakan defineModel untuk sinkronisasi v-model
const modelValue = defineModel();

const props = defineProps({
    name: {
        type: [String, Number, Array],
        default: '',
    },
    // Opsional: Untuk mengizinkan nilai desimal (koma/titik)
    allowDecimal: {
        type: Boolean,
        default: true,
    },
    // Opsional: Untuk mengizinkan nilai negatif (minus)
    allowNegative: {
        type: Boolean,
        default: false,
    },
    // Properti lainnya seperti placeholder, dll.
    placeholder: {
        type: String,
        default: '',
    },
    isValid: {
        type: Boolean,
        default: true,
    },
    isInvalid: {
        type: Boolean,
        default: true,
    },
    inputClass: {
        type: [String, Array, Object],
        default: '',
    },
    maxChar: {
        type: Number,
        default: 50,
    },
    error: {
        type: String,
        default: '',
    }
});

const inputRef = ref(null);

// Fungsi inti untuk memfilter dan memformat input
const handleInput = (event) => {
    let value = event.target.value;

    // 1. Membersihkan nilai (hanya menyisakan angka, desimal, dan minus)
    let regex = /[^0-9]/g;

    // Izinkan titik/koma (desimal)
    if (props.allowDecimal) {
        // Izinkan satu titik atau koma, dan hanya biarkan angka
        value = value.replace(/,/g, '.'); // Mengubah koma menjadi titik untuk konsistensi
        regex = new RegExp(`[^0-9${props.allowNegative ? '-' : ''}.]`, 'g');
    } else {
        // Jika desimal tidak diizinkan, hapus semua kecuali angka
        regex = new RegExp(`[^0-9${props.allowNegative ? '-' : ''}]`, 'g');
    }

    // Hapus karakter yang tidak diizinkan
    value = value.replace(regex, '');

    // 2. Menangani Desimal (Pastikan hanya ada satu titik)
    if (props.allowDecimal) {
        const parts = value.split('.');
        if (parts.length > 2) {
            // Jika ada lebih dari satu titik, gabungkan kembali bagian pertama dengan sisa bagian lainnya
            value = parts[0] + '.' + parts.slice(1).join('');
        }
    }

    // 3. Menangani Negatif (Pastikan minus hanya di awal)
    if (props.allowNegative) {
        const minusCount = (value.match(/-/g) || []).length;
        if (minusCount > 1) {
            // Hapus minus di tengah, hanya sisakan yang pertama (jika ada)
            const firstMinus = value.startsWith('-') ? '-' : '';
            value = firstMinus + value.replace(/-/g, '');
        } else if (value.indexOf('-') > 0) {
            // Pindahkan minus ke awal jika ada di tengah
            value = '-' + value.replace(/-/g, '');
        }
    }

    // 4. Update modelValue
    event.target.value = value;
    modelValue.value = value;
};

// Pastikan nilai awal modelValue juga diformat dengan benar
watch(modelValue, (newValue) => {
    if (newValue === null || newValue === undefined) return;

    // Mengubah nilai modelValue (jika diubah di luar) menjadi string yang diformat
    let strValue = String(newValue);

    // Logika pembersihan dasar (sama seperti di handleInput tapi tanpa event.target)
    let regex = new RegExp(`[^0-9${props.allowNegative ? '-' : ''}${props.allowDecimal ? '.' : ''}]`, 'g');
    strValue = strValue.replace(/,/g, '.').replace(regex, '');

    // Ini mencegah loop tak terbatas, hanya update jika ada perbedaan setelah pembersihan
    if (modelValue.value !== strValue) {
        modelValue.value = strValue;
    }
    if (newValue.length > props.maxChar) {
        modelValue.value = newValue.slice(0, props.maxChar);
    }
}, { immediate: true });


defineExpose({ focus: () => inputRef.value?.focus() });
</script>

<template>
    <input type="text" :name="props.name" :id="props.name + '_input'" :placeholder="props.placeholder" :class="['form-control', props.inputClass, {
        'is-invalid': props.isInvalid && props.error,
        'is-valid': props.isValid && modelValue && !props.error,
    }]" v-model="modelValue" ref="inputRef" />
</template>
