<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>
<template>
    <label :class="`form-label-custom fw-semibold small ${$attrs.labelClass || ''}`">
        <span v-if="value">{{ value }}</span>
        <span v-else>
            <slot />
        </span>
    </label>
</template>
