<script setup>
defineEmits(['close']);
</script>
<template>
    <header class="navbar navbar-expand bg-white px-2 sticky-top d-flex align-items-center">
        <button @click="$emit('close')" class="btn btn-light me-3 border-0 bg-transparent">
            <i class="bi bi-list fs-4"></i>
        </button>
        <h5 class="fw-bold mb-0 d-none d-sm-block" id="top-title-text">Ikhtisar Transaksi</h5>
        <div class="ms-auto"><span class="text-muted small"><i class="bi bi-clock me-1"></i> Live Session</span>
        </div>
    </header>
</template>
<style scoped>
.navbar {
    height: var(--header-height);
    background-color: #fff;
}
</style>
