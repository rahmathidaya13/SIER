<script setup>
import { usePage } from "@inertiajs/vue3"
import { ref, watch } from "vue"

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: 0
    },
    name: {
        type: String,
        default: ""
    },
    locale: {
        type: String,
        default: "id-ID" // Indonesia
    },
    currency: {
        type: String,
        default: "IDR" // Rupiah
    },
    decimals: {
        type: Number,
        default: 2
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
        default: ""
    },
    error: {
        type: String,
        default: ""
    }
})

const emit = defineEmits(["update:modelValue"])

// tampilan formatted
const displayValue = ref("")

// format number → currency
function formatCurrency(value) {
    if (value === null || value === "") return ""
    return new Intl.NumberFormat(props.locale, {
        style: "currency",
        currency: props.currency,
        minimumFractionDigits: props.decimals,
        maximumFractionDigits: props.decimals
    }).format(value)
}

// parse input string → number
function parseNumber(value) {
    if (!value) return 0

    // Hapus semua spasi dan simbol currency
    let cleaned = value.replace(/[^\d.,]/g, "")

    // Kalau ada koma → anggap itu desimal, hapus semua titik ribuan
    if (cleaned.includes(",")) {
        cleaned = cleaned.replace(/\./g, "").replace(",", ".")
    } else {
        // Kalau tidak ada koma → hapus semua titik (anggap ribuan)
        cleaned = cleaned.replace(/\./g, "")
    }

    return parseFloat(cleaned) || 0
}


// update setiap kali modelValue berubah dari parent
watch(
    () => props.modelValue,
    (val) => {
        displayValue.value = formatCurrency(val)
    },
    { immediate: true }
)

// handle input
function onInput(e) {
    const raw = e.target.value
    const parsed = parseNumber(raw)
    emit("update:modelValue", parsed)
    displayValue.value = formatCurrency(parsed)
}
const page = usePage()

// Ambil error dari inertia errors
const errors = page.props.errors
</script>

<template>
    <input type="text" :class="['form-control', inputClass, {
        'is-invalid': props.isInvalid && props.error,
        'is-valid': props.isValid && modelValue && !props.error,
    }]" :name="props.name" :id="props.name + '_input'" v-model="displayValue" @input="onInput" />
</template>
