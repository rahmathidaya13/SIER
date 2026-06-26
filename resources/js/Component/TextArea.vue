<script setup>
import { computed, onMounted, ref, watch } from "vue";
const props = defineProps({
    name: {
        type: String,
        default: ""
    },
    rows: {
        type: Number,
        default: 0
    },
    cols: {
        type: Number,
        default: 0
    },
    inputClass: {
        type: [Array, Object, String],
        default: ""
    },
    placeholder: {
        type: String,
        default: ""
    },
    maxChar: {
        type: Number,
        default: 250
    },
    error: {
        type: String,
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
})
const model = defineModel({
    type: String,
    required: true,
});

/* Hitung jumlah karakter */
const charCount = computed(() => {
    return model.value ? model.value.length : 0;
})
/* Batasi maksimal karakter */
watch(model, (newVal) => {
    if (!newVal) return;
    if (newVal.length > props.maxChar) {
        model.value = newVal.slice(0, props.maxChar);
    }
});
const textarea = ref(null);
onMounted(() => {
    // Fokuskan ke textarea jika ada atribut autofocus
    if (textarea.value && textarea.value.hasAttribute("autofocus")) {
        textarea.value.focus();
    }
});

// Mengekspos fungsi fokus
defineExpose({ focus: () => textarea.value.focus() });
</script>

<template>
    <div class="textarea-wrapper  border rounded-3 p-2 transition" :class="{
        'border-danger': props.isInvalid && props.error,
        'border-success': props.isValid && model && !props.error,
    }">
        <textarea ref="textarea" v-model="model" :rows="rows" :cols="cols" :name="props.name"
            :id="props.name + '_input'" :placeholder="props.placeholder || 'Masukkan deskripsi...'"
            class="form-control border-0 shadow-none resize-none" :class="inputClass"></textarea>

        <div class="d-flex justify-content-end align-items-center mt-1 px-1">
            <small class="fw-semibold" :class="charCount === props.maxChar ? 'text-danger' : 'text-secondary'">
                {{ charCount }}/{{ props.maxChar }}
            </small>
        </div>
    </div>

</template>
