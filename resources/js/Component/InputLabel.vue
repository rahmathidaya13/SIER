<script setup>
defineProps({
    value: {
        type: String,
    },
    labelClass: {
        type: String,
        default: "",
    },
});
</script>
<template>
    <label :class="`form-label-custom fw-semibold small ${labelClass || ''}`">
        <span v-if="value">{{ value }}</span>
        <span v-else>
            <slot />
        </span>
    </label>
</template>
