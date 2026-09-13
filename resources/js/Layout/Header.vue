<script setup>
import { dateConvert } from '@/Helpers/dateConvert';
const emit = defineEmits(['close']);

</script>
<template>
    <header class="navbar navbar-expand bg-white px-2 sticky-top d-flex align-items-center justify-content-between">
        <div class="head-title d-flex align-items-center gap-1">
            <button @click="emit('close')" class="btn btn-danger btn-lg text-danger border-0 bg-transparent">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span>
                {{ $page.props.app.name ?? 'Aplikasi' }}
            </span>
        </div>
        <div style="font-size: 13px;" class="text-muted text-uppercase fw-bold d-none d-xl-block">
            {{ dateConvert(new Date().toISOString(), 'DD/MM/YYYY - HH:mm') }}
        </div>
    </header>
</template>
<style scoped>
.navbar {
    height: var(--header-height);
    background-color: #fff;
}

.head-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--primary-color);
    height: var(--header-height);
    letter-spacing: 0.5px;
}
</style>
