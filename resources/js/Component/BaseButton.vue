<script setup>
defineProps({
    variant: {
        type:  [String, Array, Object],
        default: "primary",
    },
    label: {
        type: String,
        default: "",
    },
    icon: {
        type: String,
        default: null,
    },
    name: {
        type: String,
        default: "",
    },
    loading: {
        type: Boolean,
        default: false,
    },
    waiting: {
        type: String,
        default: "Please wait...",
    },
    buttonClass: {
        type: [String, Array, Object],
        default: "",
    }
});
</script>
<template>
    <button :class="['btn', `btn-${variant}`, buttonClass]" :name="name.toLowerCase()" :id="name.toLowerCase()"
        :disabled="loading">
        <span v-if="loading"><i class="fas fa-spinner fa-spin"></i> {{ waiting }}</span>
        <span v-else>
            <slot><i v-if="icon" :class="[icon]"></i>
                {{ label }}
            </slot>
        </span>
    </button>
</template>
