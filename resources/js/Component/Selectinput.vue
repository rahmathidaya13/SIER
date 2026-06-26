<script setup>
import { computed, onMounted } from 'vue'
const props = defineProps({
    modelValue: {
        type: [String, Number, Array, Function, null],
        default: null
    },
    text: {
        type: String,
        default: ""
    },
    name: {
        type: String,
        default: ""
    },
    options: {
        type: Array,
        default: () => []
    },
    disabled: {
        type: [Boolean, String],
        default: false
    },
    selectClass: {
        type: [String, Array, Object],
        default: ""
    },
    isValid: {
        type: Boolean,
        default: true,
    },
    isInvalid: {
        type: Boolean,
        default: true,
    },
    error: {
        type: String,
        default: ""
    }
})
const emit = defineEmits(['update:modelValue'])
const selectedValue = computed({
    get() {
        return props.modelValue
    },
    set(val) {
        emit('update:modelValue', val)
    }
})
function formatLabel(label) {
    return String(label).replace(/\b\w/g, l => l.toUpperCase())
}
</script>
<template>
    <select v-model="selectedValue" :disabled="disabled" :class="['form-select', selectClass, {
        'is-invalid': props.isInvalid && props.error,
        'is-valid': props.isValid && props.modelValue && !props.error,
    }]" :name="props.name" :id="props.name + '_select'">
        <option v-if="props.text" :value="null" disabled>{{ props.text }}</option>
        <option v-for="opt in options" :key="opt.value" :value="opt.value">
            {{ formatLabel(opt.label) }}
        </option>
    </select>
</template>
