<script setup>
import { ref, watch } from 'vue';

const modelValue = defineModel({
    type: [String, Number],
    default: ''
})
const props = defineProps({
    name: {
        type: String,
        default: ''
    },
    decimalPlaces: {
        type: Number,
        default: 2,
    },
    placeholder: {
        type: String,
        default: '0.00',
    },
    // Opsional: Untuk mengizinkan nilai negatif
    allowNegative: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    }
})
const inputRef = ref(null);

function cleanNumberInput(value) {
    if (typeof value !== 'string') return '';

    // 1. Ganti koma dengan titik (standar desimal)
    let cleaned = value.replace(/,/g, '.');

    // 2. Hapus semua karakter yang bukan angka, titik, atau minus
    let regex = new RegExp(`[^0-9${props.allowNegative ? '-' : ''}.]`, 'g');
    cleaned = cleaned.replace(regex, '');

    // 3. Menangani Negatif (Pastikan minus hanya di awal)
    if (props.allowNegative) {
        // Hapus semua minus kecuali yang pertama
        const firstMinus = cleaned.startsWith('-') ? '-' : '';
        cleaned = firstMinus + cleaned.replace(/-/g, '');
    } else {
        // Jika negatif tidak diizinkan, hapus semua minus
        cleaned = cleaned.replace(/-/g, '');
    }

    // 4. Menangani Desimal (Pastikan hanya ada satu titik)
    const parts = cleaned.split('.');
    if (parts.length > 2) {
        // Jika ada lebih dari satu titik, gabungkan kembali bagian pertama dengan sisanya tanpa titik tambahan
        cleaned = parts[0] + '.' + parts.slice(1).join('');
    }

    // 5. Batasi jumlah desimal setelah titik
    if (parts.length === 2 && parts[1].length > props.decimalPlaces) {
        cleaned = parts[0] + '.' + parts[1].substring(0, props.decimalPlaces);
    }

    return cleaned;
}
const handleInput = (event) => {
    let value = event.target.value;

    // Bersihkan nilai input
    const cleanedValue = cleanNumberInput(value);

    // Update input DOM (agar pengguna melihat input yang sudah dibersihkan)
    event.target.value = cleanedValue;

    // Update modelValue
    modelValue.value = cleanedValue;
};

// Gunakan watcher untuk memastikan nilai modelValue selalu bersih, bahkan jika diubah dari luar
watch(modelValue, (newValue) => {
    if (newValue === null || newValue === undefined || newValue === '') return;

    const cleanedValue = cleanNumberInput(String(newValue));

    // Hanya update jika nilai bersih berbeda, untuk menghindari loop tak terbatas
    if (modelValue.value !== cleanedValue) {
        modelValue.value = cleanedValue;
    }
}, { immediate: true });


defineExpose({ focus: () => inputRef.value?.focus() });
</script>
<template>
    <input type="text" :name="props.name" :id="props.name + '_input'" :placeholder="props.placeholder" :class="['form-control', {
        'is-invalid': props.isInvalid && props.error,
        'is-valid': props.isValid && modelValue && !props.error,
    }]" v-model="modelValue" ref="inputRef" />
</template>
