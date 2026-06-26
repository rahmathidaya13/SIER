<script setup>
const emit = defineEmits(['submit']);

function handleSubmit(event) {
    event.preventDefault();
    emit('submit', event);
}
</script>
<template>
    <form @submit="handleSubmit" enctype="multipart/form-data" role="form">
        <slot />
    </form>
</template>
