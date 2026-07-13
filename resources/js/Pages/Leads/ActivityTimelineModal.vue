<script setup>
import { ref, watch, computed } from "vue";
import { formatDate } from "@/Helpers/formatDate";
import axios from "axios";

const props = defineProps({
    show: Boolean,
    idLead: {
        type: String,
        default: null
    }
});

const emit = defineEmits(["update:show"]);

const isLoading = ref(false);
const activities = ref([]);
const customerName = ref("");
const productName = ref("");

const close = () => {
    emit("update:show", false);
    activities.value = [];
    customerName.value = "";
    productName.value = "";
};

const loadTimeline = async () => {
    if (!props.idLead) return;

    isLoading.value = true;
    try {
        // Kita manfaatkan endpoint show yang sudah membawa relasi lead.activity
        const { data } = await axios.get(`/leads/show/${props.idLead}`);
        console.log(data);
        customerName.value = data?.name || 'Konsumen';
        activities.value = data?.activity || [];
        productName.value = data?.product?.product_name || '';

    } catch (error) {
        console.error("Gagal mengambil riwayat:", error);
    } finally {
        isLoading.value = false;
    }
};

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            loadTimeline();
        }
    }
);

// Filter Cerdas: Menentukan Icon dan Warna berdasarkan isi teks
const getTimelineStyle = (description) => {
    if (description.includes('💰')) {
        return { icon: 'bi-cash-stack', color: 'success', bg: 'bg-success-subtle', text: 'text-success' };
    }
    if (description.includes('🎉') || description.includes('DEAL')) {
        return { icon: 'bi-bag-check-fill', color: 'primary', bg: 'bg-primary-subtle', text: 'text-primary' };
    }
    if (description.includes('❌') || description.includes('LOST')) {
        return { icon: 'bi-x-circle-fill', color: 'danger', bg: 'bg-danger-subtle', text: 'text-danger' };
    }
    // Default untuk Follow Up biasa
    return { icon: 'bi-chat-dots-fill', color: 'secondary', bg: 'bg-light', text: 'text-secondary' };
};
const getRoleName = (roleName) => {
    const roles = {
        'super_admin': {
            label: 'Head Office',
            color: 'bg-dark text-white'
        },
        'supervisor': {
            label: 'Supervisor',
            color: 'bg-primary text-white'
        },
        'admin': {
            label: 'Admin',
            color: 'bg-info text-dark'
        },
        'sales': {
            label: 'Sales',
            color: 'bg-success text-white'
        },
        'teknisi': {
            label: 'Teknisi',
            color: 'bg-secondary text-white'
        }
    };
    return roles[roleName] || 'bg-light text-dark border';
};
</script>

<template>
    <div v-if="props.show">
        <modal width="650px" size="modal-md" :icon="['text-primary', 'bi bi-clock-history']" :show="props.show"
            title="Riwayat Jejak Rekam Aktivitas" @closed="close" :mobileFullscreen="true">

            <template #body>
                <div class="bg-c-slate text-white p-3 rounded-3 mb-4 shadow-sm d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width: 45px; height: 45px;">
                        <i class="bi bi-person-lines-fill fs-4 text-white"></i>
                    </div>
                    <div>
                        <p class="mb-0 text-white-50 small fw-medium text-uppercase tracking-wide">Timeline Konsumen</p>
                        <h5 class="mb-0 fw-bold">{{ customerName }}</h5>
                        <small class="mb-0 fw-semibold text-info">{{ productName }}</small>
                    </div>
                </div>

                <div v-if="isLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3 small fw-medium">Menarik data dari arsip...</p>
                </div>

                <div v-else-if="activities.length > 0" class="timeline-container px-2 pb-3">

                    <div v-for="(act, index) in activities" :key="act.id_lead_activity"
                        class="timeline-item position-relative mb-4">

                        <div v-if="index !== activities.length - 1" class="timeline-line"></div>

                        <div class="d-flex gap-3">
                            <div class="timeline-icon flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center shadow-sm z-2"
                                :class="getTimelineStyle(act.description).bg"
                                style="width: 40px; height: 40px; border: 3px solid #fff;">
                                <i class="fs-5"
                                    :class="[getTimelineStyle(act.description).icon, getTimelineStyle(act.description).text]"></i>
                            </div>

                            <div class="flex-grow-1 bg-white border rounded-3 p-3 shadow-sm">
                                <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="badge bg-light text-dark border small">
                                            <i class="bi bi-person-fill me-1"></i>
                                            <span class="text-capitalize">
                                                {{ act.user?.activity_creator_name || 'Sistem' }} - {{
                                                    getRoleName(act.user?.roles[0]?.name).label }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-muted small fw-medium" style="font-size: 11px;">
                                        <i class="bi bi-calendar2-event me-1"></i> {{ formatDate(act.created_at) }}
                                    </span>
                                </div>

                                <p class="mb-0 text-dark"
                                    style="font-size: 14px; white-space: pre-line; line-height: 1.5;">
                                    {{ act.description }}
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                <div v-else class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted opacity-25 d-block mb-3"></i>
                    <h6 class="text-muted fw-bold">Belum Ada Riwayat</h6>
                    <p class="text-muted small">Konsumen ini belum memiliki jejak rekam atau aktivitas pembayaran.</p>
                </div>
            </template>

            <template #footer>
                <div class="d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary fw-bold px-4 shadow-sm" @click="close">
                        <i class="bi bi-x-circle me-1"></i> TUTUP
                    </button>
                </div>
            </template>

        </modal>
    </div>
</template>

<style scoped>
.timeline-container {
    position: relative;
    max-height: 500px;
    overflow-y: auto;
    overflow-x: hidden;
}

/* Kustomisasi Scrollbar agar elegan */
.timeline-container::-webkit-scrollbar {
    width: 6px;
}

.timeline-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.timeline-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.timeline-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Garis Vertikal Timeline */
.timeline-line {
    position: absolute;
    left: 19px;
    top: 40px;
    bottom: -24px;
    width: 2px;
    background-color: #e2e8f0;
    z-index: 1;
}

.tracking-wide {
    letter-spacing: 1px;
}
</style>
