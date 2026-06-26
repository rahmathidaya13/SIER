<script setup>
defineProps({
    message: {
        type: String,
        required: true,
        default: "",
    },
});
</script>
<template>
    <div class="invalid-feedback" v-if="message">
        {{ message }}
    </div>
</template>
