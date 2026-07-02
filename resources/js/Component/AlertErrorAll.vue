<script setup>
import { computed } from "vue";

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },

    title: {
        type: [String, Boolean],
        default: "⚠️ Waduh! Ada beberapa kesalahan.",
    },
    variant: {
        type: String,
        default: "alert-c-danger",
    },
    dismissible: {
        type: Boolean,
        default: true,
    },
});
const emit = defineEmits(["close"]);
const errorList = computed(() => {
    return Object.values(props.errors ?? {}).flat();
});

const hasErrors = computed(() => errorList.value.length > 0);
const close = () => emit("close");
</script>

<template>
    <Transition name="slide-fade">
        <div v-if="hasErrors" class="alert shadow-sm" :class="`${variant}`" role="alert">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div v-if="title" class="fw-bold mb-2">
                        {{ title }}
                    </div>
                    <ul class="mb-0 ps-3">
                        <li v-for="(error, index) in errorList" :key="index" class="small">
                            {{ error }}
                        </li>
                    </ul>
                </div>
                <button v-if="dismissible" class="btn-close ms-3 bg-transparent rounded-circle border-0 small" @click="close" />
            </div>
        </div>
    </Transition>
</template>
<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: .25s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
