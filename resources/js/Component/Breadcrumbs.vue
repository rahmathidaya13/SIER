<script setup>
defineProps({
    breadcrumbs: {
        type: Array,
        default: () => []
    }
})
</script>
<template>
    <nav aria-label="breadcrumb" class="d-none d-sm-block">
        <ol class="breadcrumb mb-0">

            <li v-for="(item, index) in breadcrumbs" :key="index" class="breadcrumb-item" :class="{
                active: item.active
            }">
                <template v-if="!item.active">
                    <a :href="item.href || '#'" class="text-muted text-decoration-none">
                        {{ item.label }}
                    </a>
                </template>

                <template v-else>
                    <span class="fw-bold">
                        {{ item.label }}
                    </span>
                </template>
            </li>

        </ol>
    </nav>
</template>
