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
    }
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
        'border-danger': $page.props.errors[props.name],
        'border-success': model && !$page.props.errors[props.name],
    }">
        <textarea ref="textarea" v-model="model" :rows="rows" :cols="cols" :name="props.name" :id="props.name"
            :placeholder="props.placeholder || 'Masukkan deskripsi...'"
            class="form-control border-0 shadow-none resize-none" :class="inputClass"></textarea>

        <div class="d-flex justify-content-end align-items-center mt-1 px-1">
            <small class="fw-semibold" :class="charCount === props.maxChar ? 'text-danger' : 'text-secondary'">
                {{ charCount }}/{{ props.maxChar }}
            </small>
        </div>
    </div>

</template>
<style scoped>
.textarea-wrapper {
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.textarea-wrapper:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.1rem rgba(13, 110, 253, 0.15);
}

.resize-none {
    resize: none;
}
</style>
