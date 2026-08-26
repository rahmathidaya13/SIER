<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useFilter } from '@/Composables/useFilter';
import { hasRole, hasPermission } from '@/Composables/useAuth';
import { formatDate } from '@/Helpers/formatDate';

const props = defineProps({
    notifications: Object
})
// Fungsi untuk klik "Tandai Semua"
const markAllAsRead = () => {
    router.post(route('notifications.readAll'), {}, {
        preserveScroll: true, // Layar tidak akan lompat ke atas saat tombol diklik
    });
};

// Fungsi untuk klik per item
const markAsRead = (notification) => {
    // Jika sudah dibaca, tidak perlu panggil backend lagi
    if (notification.read_at !== null) return;
    console.log(notification);
    router.post(route('notifications.read', notification.id), {}, {
        preserveScroll: true,
    });
};
console.log(props.notifications.data);
const header = [
    {
        label: "No",
        key: "__index",
        attrs: {
            class: 'text-center td-number',
        },
    },
    {
        label: "Judul",
        key: "title",
        attrs: {
            style: {
                width: '25%'
            },
            class: 'text-start',
        },
    },
    {
        label: "Notifikasi",
        key: "message",
        attrs: {
            style: {
                width: '25%'
            },
            class: 'text-start',
        },
    },
    {
        label: "Aksi",
        key: "action",
        attrs: {
            class: 'text-center',
        },
    },

];
const rowClasses = (item) => ({
    'tr-dimmed': item.read_at !== null,
    'tr-highlight-primary': item.read_at === null,
})
</script>
<template>

    <Head title="Notifikasi" />
    <app-layout>
        <template #content>
            <div class="row g-3 view-section">
                <div class="col-12">
                    <div class="card border-0 rounded-4 overflow-hidden">
                        <div class="card-header p-4 bg-white">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h5 class="fw-bold mb-1">Daftar Notifikasi</h5>
                                </div>
                                <button v-if="!notifications.data.length > 0" @click.prevent="markAllAsRead"
                                    class="btn btn-sm btn-c-slate px-4 py-2">
                                    <i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table-wrapper :rowClass="rowClasses" :headers="header" :items="notifications?.data"
                                rowKey="id">

                                <template #empty>
                                    <div class="text-center py-5">
                                        <i class="bi bi-funnel fa-3x text-muted opacity-25 mb-3 d-block"></i>
                                        <h6 class="text-muted fw-bold">
                                            Data Notifikasi Masih Kosong
                                        </h6>
                                    </div>
                                </template>

                                <template #row="{ item, index }">
                                    <td class="td-number">
                                        {{ index + 1 + (notifications?.current_page - 1) * notifications?.per_page }}
                                    </td>

                                    <td>
                                        <div class="text-muted">
                                            {{ item.data.title }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold d-flex flex-column">
                                            {{ item.data.message }}
                                            <small class="text-muted" style="font-size: 11px;">
                                                {{ new Date(item.created_at).toLocaleString() }}
                                            </small>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <button v-if="!item.read_at" @click.prevent="markAsRead(item)" type="button"
                                            class="btn btn-c-slate" title="Tandai Dibaca">
                                            <i class="bi bi-check"></i>
                                        </button>
                                        <span v-else class="text-success fw-semibold">
                                            <i class="bi bi-check-circle"></i>
                                            Ditandai
                                        </span>
                                    </td>


                                </template>


                                <template #pagination>
                                    <div
                                        class="border-top py-2 px-2 d-flex flex-column flex-md-row justify-content-between align-items-center ">
                                        <small class="text-muted mb-2 mb-md-0">
                                            Menampilkan <strong>{{ notifications.from ?? 0 }}</strong> - <strong>{{
                                                notifications.to ?? 0 }}</strong> dari <strong>{{ notifications.total
                                                    ?? 0
                                                }}</strong>
                                        </small>
                                        <!-- <pagination size="pagination-sm" :links="notifications?.links"
                                            routeName="notification.index" :additionalQuery="{
                                                keyword: filters.keyword,
                                                limit: filters.limit,
                                                order_by: filters.order_by,
                                            }" :only="['notifications']" /> -->
                                    </div>
                                </template>


                            </table-wrapper>
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </app-layout>
</template>
