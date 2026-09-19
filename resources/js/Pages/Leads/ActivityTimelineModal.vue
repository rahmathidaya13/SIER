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
const phoneNumber = ref('');
const products = ref(null);
const close = () => {
    emit("update:show", false);
    activities.value = [];
    customerName.value = "";
    phoneNumber.value = '';
    products.value = null;
};

const loadTimeline = async () => {
    if (!props.idLead) return;

    isLoading.value = true;
    try {
        // Kita manfaatkan endpoint show yang sudah membawa relasi lead.activity
        const { data } = await axios.get(`/leads/show/${props.idLead}`);
        const cleanData = data.data;
        customerName.value = cleanData?.name || 'Konsumen';
        phoneNumber.value = cleanData?.phone || '';
        // activities.value = cleanData?.activity || [];
        products.value = cleanData?.products || null;
        // console.log(cleanData);
    } catch (error) {
        console.error("Gagal mengambil riwayat:", error);
    } finally {
        isLoading.value = false;
    }
};

const loadActivities = async (page = 1) => {
    if (!props.idLead) return;
    isLoading.value = true;
    try {
        const { data } = await axios.get(
            route('leads.activities', props.idLead),
            {
                params: {
                    page,
                },
            }
        );
        const cleanData = data.data;
        activities.value = cleanData;
        console.log(data);
    } catch (error) {
        console.error("Gagal mengambil riwayat:", error);
    } finally {
        isLoading.value = false;
    }

};

watch(
    () => props.show,
    async (isOpen) => {
        if (isOpen) {
            loadTimeline();
            loadActivities();
        }
    }, {
    immediate: true
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
const customerType = (type) => {
    const types = {
        'NEW_CUSTOMER': {
            label: 'Pelanggan Baru',
            color: 'bg-success text-white'
        },
        'REPEAT_ORDER': {
            label: 'Pelanggan Lama/Beli Ulang',
            color: 'bg-primary text-white'
        },
        'WIN_BACK': {
            label: 'Pelanggan Cancel',
            color: 'bg-danger text-white'
        },
    };
    return types[type] || 'bg-light text-dark border';
};
const getShortDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}
const getTimeOnly = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div v-if="props.show">
        <modal width="750px" size="modal-md" :icon="['text-primary', 'bi bi-clock-history']" :show="props.show"
            title="Riwayat Jejak Rekam Aktivitas" @closed="close" :mobileFullscreen="false">

            <template #body>

                <div v-if="isLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-3 small fw-medium">Menarik data dari arsip...</p>
                </div>

                <div v-else class="timeline-container px-2 pb-3">

                    <div class="bg-c-slate text-white p-3 rounded-3 mb-4 shadow-sm d-flex align-items-center gap-3">

                        <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width: 45px; height: 45px;">
                            <i class="bi bi-person-lines-fill fs-4 text-white"></i>
                        </div>

                        <div class="d-flex flex-column justify-content-center">
                            <p class="mb-0 text-white-50 small fw-medium text-uppercase tracking-wide"
                                style="font-size: 10.5px;">Timeline Konsumen</p>
                            <h5 class="mb-1 fw-bold">{{ customerName }}</h5>
                            <span class="small fw-medium">
                                <i class="bi bi-telephone-fill me-1"></i>
                                {{ phoneNumber }}
                            </span>

                            <div v-if="products && products.length > 0" class="d-flex flex-wrap gap-1 mt-1">

                                <template v-for="(prod, index) in products" :key="prod.id_product || index">
                                    <span
                                        class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 fw-normal d-flex align-items-center py-1 px-2 rounded-pill shadow-sm"
                                        style="font-size: 11px;">
                                        <i class="bi bi-box-seam me-1 opacity-75"></i>
                                        <span class="text-truncate" style="max-width: 130px;"
                                            :title="prod.product_name">
                                            {{ prod.product_name }}
                                        </span>
                                    </span>
                                </template>
                            </div>

                            <div v-else class="mt-1">
                                <span
                                    class="badge bg-white bg-opacity-10 text-white-50 border border-white border-opacity-10 fw-normal py-1 px-2 rounded-pill fst-italic"
                                    style="font-size: 10.5px;">
                                    Belum ada produk
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="animate-fade-in">
                        <div v-if="activities.length > 0" class="tracking-container pb-3">

                            <div v-for="(act, index) in activities" :key="act.id_lead_activity"
                                class="d-flex align-items-stretch gap-3">

                                <div class="flex-shrink-0 text-end pe-3" style="width: 70px; padding-top: 2px;">
                                    <div class="fw-medium tracking-date text-muted">
                                        {{ getShortDate(act.created_at) }}
                                    </div>
                                    <div class="tracking-time text-muted opacity-75">
                                        {{ getTimeOnly(act.created_at) }}
                                    </div>
                                </div>

                                <div class="position-relative d-flex flex-column align-items-center"
                                    style="width: 5px;">

                                    <div class="rounded-circle z-2"
                                        :class="index === 0 ? 'bg-success' : 'bg-secondary opacity-50'"
                                        :style="index === 0 ? 'width: 10px; height: 10px; margin-top: 4px; box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.2);' : 'width: 8px; height: 8px; margin-top: 5px;'">
                                    </div>

                                    <div v-if="index !== activities.length - 1"
                                        class="bg-secondary opacity-25 position-absolute"
                                        style="width: 2px; top: 16px; bottom: -6px; left: 50%; transform: translateX(-50%); z-index: 1;">
                                    </div>
                                </div>

                                <div class="flex-grow-1 ps-3 pb-3">

                                    <div class="tracking-desc mb-1"
                                        :class="index === 0 ? 'text-success fw-medium' : 'text-muted'"
                                        style="white-space: pre-line;">
                                        {{ act.description }}
                                    </div>

                                    <div
                                        class="tracking-user text-muted opacity-75 d-flex align-items-center gap-1 mt-1">
                                        <i class="bi bi-person"></i>
                                        <span class="text-capitalize">{{ act.user?.name || 'Sistem' }}</span>
                                        <span v-if="act.created_at !== act.updated_at" class="fst-italic ms-1">
                                            (Diubah: {{ act.user?.name }} - {{ getTimeOnly(act.updated_at) }})
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="empty-state">
                            <div class="empty-icon bg-light text-muted mb-3"><i class="bi bi-clock-history"></i></div>
                            <h6 class="text-muted fw-bold">Belum Ada Riwayat</h6>
                            <p class="text-muted small">Belum ada jejak rekam atau aktivitas untuk konsumen ini.</p>
                        </div>
                    </div>

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
.animate-fade-in {
    animation: fadeInSlideUp 0.35s ease-out forwards;
}

@keyframes fadeInSlideUp {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

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

/* Kustomisasi Empty State */
.empty-state {
    min-height: 260px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 40px 20px;
}

.empty-state>i {
    font-size: 48px;
    color: #cbd5e1;
    opacity: 0.7;
}

.empty-icon {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}

/* Tracking Activity UI Styles */
.tracking-container {
    max-height: 520px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 8px;
}

.tracking-container::-webkit-scrollbar {
    width: 5px;
}

.tracking-container::-webkit-scrollbar-track {
    background: transparent;
}

.tracking-container::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.tracking-container::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

/* Tipografi Presisi */
.tracking-date {
    font-size: 12px;
    line-height: 1.2;
    letter-spacing: 0.2px;
}

.tracking-time {
    font-size: 11px;
    margin-top: 2px;
}

.tracking-desc {
    font-size: 14px;
    line-height: 1.55;
    letter-spacing: 0.1px;
    word-break: break-word;
    white-space: pre-wrap;
    word-wrap: break-word;
    hyphens: auto;
}

.tracking-user {
    font-size: 12px;
}
</style>
