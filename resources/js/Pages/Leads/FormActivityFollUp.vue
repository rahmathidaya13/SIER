<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import { useRowAlert } from '@/Composables/useAlert';
import { currencyFormat } from "@/Helpers/currencyFormat";
import { formatDate } from "@/Helpers/formatDate";
import { debounce } from "lodash";
import axios from "axios";
const { show, __alert, hide } = useRowAlert();

const props = defineProps({
    idLead: {
        type: String,
        default: null
    },
    show: Boolean,
});

const isEdit = computed(() => !!props.idLead);
const isLoading = ref(false);
const dataLead = ref(null);

const form = useForm({
    description: '',
});

const fillForm = (data) => {
    form.defaults({
        description: '',
    });

    dataLead.value = data
    form.reset();
}

const resetForm = () => {
    form.defaults({
        description: '',
    })
    form.reset();
    form.clearErrors();
    dataLead.value = null
}


const loadLead = async () => {
    isLoading.value = true

    if (!props.idLead) {
        resetForm();
        return
    }

    try {
        const { data } = await axios.get(`/leads/show/${props.idLead}`);
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    }
    finally {
        isLoading.value = false
    }

}
const activityEdit = reactive({
    id: null,
    new_value: '',
});
const editActivity = (activities) => {
    console.log(activities);
    activityEdit.id = activities.id_lead_activity;
    activityEdit.new_value = activities.description || '';
}
const cancelEditActivity = () => {
    activityEdit.id = null;
    activityEdit.new_value = '';
}
const updateActivity = () => {
    router.put(route('leads.activity.update', activityEdit.id),
        {
            description: activityEdit.new_value
        }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            const activity = dataLead.value.activity.find(
                actv => actv.id_lead_activity === activityEdit.id
            );
            if (activity) {
                activity.description = activityEdit.new_value;
            }
            show({
                id: activityEdit.id,
                message: 'Aktivitas berhasil diperbarui',
                type: 'success',
                duration: 5000
            });
            cancelEditActivity();
        },
        onError: (errors) => {
            show({
                id: activityEdit.id,
                message: errors.description,
                type: 'danger',
                duration: 5000
            });
        }
    });
}
const submit = () => {
    const url = route('leads.activity.store', props.idLead);
    form.post(url, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: async () => {
            // close();
            await loadLead();
        },
    });
};
// watch untuk kondisi buka modal, jika props.show berubah menjadi true, maka loadLead akan dijalankan
watch(
    () => props.show,
    async (isOpen) => {
        if (!isOpen) return;
        if (props.idLead) {
            await loadLead();
        } else {
            resetForm();
        }

    }, {
    immediate: true
}
);
const emit = defineEmits(["update:show", "saved"]);
const close = () => {
    emit("update:show", false);
    resetForm();
};

const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return isEdit.value ? 'Simpan Perubahan' : 'Simpan';
});
const getRoleName = (data, mode = 'create') => {
    let role;
    if (mode === 'create') {
        role = data.user.roles[0].name
    }
    if (mode === 'edit') {
        role = data.user_update.roles[0].updated_by_name
    }
    // const role = data.user.roles.map(role => role.name)
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
    return roles[role] || 'bg-light text-dark border';
};
</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="750px" size="modal-lg" :icon="[
                'text-coffee-main',
                {
                    'bi bi-pencil-square text-primary': isEdit,
                    'bi bi-plus-circle-fill text-primary': !isEdit
                }
            ]" :show="props.show" title="Aktivitas Follow Up" :mobileFullscreen="false" @closed="close">

                <template #body>

                    <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

                    <div v-if="$page.props.flash.modal_message && !isLoading"
                        class="alert alert-c-success py-3 px-3 mb-3 d-flex align-items-center justify-content-between rounded-4"
                        style="font-size: 15px;">
                        <div class="d-flex align-items-center">
                            <i class="me-1 fs-6 bi bi-check-circle-fill text-success"></i>
                            <span class="fw-medium">{{ $page.props.flash.modal_message }}</span>
                        </div>
                        <button type="button" class="btn-close"
                            @click="$page.props.flash.modal_message = null"></button>
                    </div>


                    <div v-if="isLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-3 small fw-medium">Sedang mengambil data...</p>
                    </div>
                    <div v-else>
                        <form-wrapper @submit.prevent="submit">
                            <div class="row g-3 animate-fade-in">

                                <div class="col-12">
                                    <div class=" p-3 rounded-4 border position-relative">
                                        <label for="activity_input"
                                            class="form-label small fw-bold text-primary mb-2 d-flex align-items-center">
                                            <i class="bi bi-chat-right-dots-fill me-2 fs-6"></i> BUAT CATATAN FOLLOW-UP
                                            BARU
                                        </label>
                                        <text-area :maxChar="2500" :error="form.errors.description" name="description"
                                            v-model="form.description" :rows="5"
                                            inputClass="border-0 shadow-sm rounded-3"
                                            placeholder="Tuliskan apa yang baru saja dibicarakan dengan pelanggan di sini..." />
                                        <input-error :message="form.errors.description" />
                                    </div>
                                </div>

                                <div v-if="dataLead?.activity && dataLead?.activity.length > 0"
                                    class="col-12 mt-4 pt-2">

                                    <h6 class="fw-bold text-muted mb-4 pb-2 border-bottom d-flex align-items-center"
                                        style="font-size: 13px; letter-spacing: 0.5px;">
                                        <i class="bi bi-clock-history me-2 fs-5"></i> JEJAK REKAM KONSUMEN (TIMELINE)
                                    </h6>

                                    <div class="timeline-container position-relative ps-4 pe-2"
                                        style="max-height: 480px; overflow-y: auto;">
                                        <div class="position-absolute h-100 border-start border-2 border-secondary-subtle"
                                            style="left: 15px; top: 8px; z-index: 1;"></div>

                                        <div class="position-relative mb-4" v-for="act in dataLead.activity"
                                            :key="act.id_lead_activity">
                                            <div class="position-absolute bg-primary rounded-circle border border-3 border-white shadow-sm"
                                                style="width: 18px; height: 18px; left: -24px; top: 12px; z-index: 2;">
                                            </div>

                                            <div class="card border shadow-sm rounded-4 overflow-hidden">

                                                <div
                                                    class="card-header bg-primary-subtle border-bottom-0 py-2 px-3 d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <div class="badge bg-primary text-white rounded-circle d-flex justify-content-center align-items-center shadow-sm"
                                                            style="width: 36px; height: 36px;">
                                                            <i class="bi bi-person-fill fs-5"></i>
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold text-dark text-capitalize"
                                                                style="font-size: 13.5px;">
                                                                {{ act.user?.activity_creator_name || 'Sistem' }}
                                                                <span
                                                                    class="badge bg-secondary-subtle text-secondary ms-1 fw-medium border border-secondary-subtle"
                                                                    style="font-size: 10px;">
                                                                    {{ getRoleName(act, 'create').label }}
                                                                </span>
                                                            </span>
                                                            <span class="text-muted mt-1 d-flex align-items-center"
                                                                style="font-size: 11px;">
                                                                <i class="bi bi-calendar-event me-1"></i> {{
                                                                    formatDate(act.created_at) }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div v-if="act.created_at !== act.updated_at && act.id_user !== act.updated_by"
                                                        class="text-end">
                                                        <span
                                                            class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill fw-medium px-2 py-1"
                                                            style="font-size: 10px;">
                                                            <i class="bi bi-pencil-square me-1"></i> Diubah: {{
                                                                act.user_update?.name }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="card-body p-3">

                                                    <div v-if="__alert.visible && __alert.id === act.id_lead_activity"
                                                        class="alert py-2 px-3 mb-3 d-flex align-items-center rounded-3"
                                                        :class="`alert-${__alert.type}`" style="font-size: 13px;">
                                                        <i :class="{ 'bi bi-check-circle-fill text-success': __alert.type === 'success', 'bi bi-exclamation-triangle-fill text-danger': __alert.type === 'danger' }"
                                                            class="me-2 fs-5"></i>
                                                        <span class="fw-medium">{{ __alert.message }}</span>
                                                    </div>

                                                    <p v-if="!activityEdit.id" class="mb-0 text-dark"
                                                        style="font-size: 14px; white-space: pre-line; line-height: 1.6;">
                                                        {{ act.description }}
                                                    </p>

                                                    <div v-if="activityEdit.id === act.id_lead_activity" class="mb-2">
                                                        <text-area
                                                            :error="__alert.visible && __alert.id === act.id_lead_activity"
                                                            :maxChar="2500" name="activity"
                                                            inputClass="bg-light border-secondary-subtle rounded-3"
                                                            v-model="activityEdit.new_value" :rows="3" />
                                                    </div>

                                                    <div class="d-flex gap-2 justify-content-end mt-3 border-top pt-2">
                                                        <button v-if="!activityEdit.id"
                                                            @click.prevent="editActivity(act)"
                                                            class="btn btn-sm btn-light text-primary fw-medium rounded-pill px-3 shadow-none transition-all hover-bg-primary-subtle"
                                                            style="font-size: 12px;">
                                                            <i class="bi bi-pencil me-1"></i> Edit
                                                        </button>

                                                        <template v-else-if="activityEdit.id === act.id_lead_activity">
                                                            <button @click.prevent="cancelEditActivity"
                                                                class="btn btn-sm btn-light text-danger fw-medium rounded-pill px-3 shadow-none"
                                                                style="font-size: 12px;">
                                                                Batal
                                                            </button>
                                                            <button @click.prevent="updateActivity"
                                                                class="btn btn-sm btn-primary fw-medium rounded-pill px-3 shadow-sm"
                                                                style="font-size: 12px;">
                                                                <i class="bi bi-save me-1"></i> Simpan
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="!dataLead?.activity || dataLead?.activity.length === 0"
                                    class="col-12 text-center text-muted py-5">
                                    <i class="bi bi-chat-square-dots fs-1 opacity-25 d-block mb-2"></i>
                                    <small class="fw-medium">Belum ada riwayat percakapan pada prospek ini.</small>
                                </div>
                            </div>
                        </form-wrapper>
                    </div>


                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 bg-transparent">
                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4"
                            @click.prevent="close">Batal</button>
                        <button @click.prevent="submit" type="button" :disabled="form.processing || isLoading"
                            class="btn btn-height-1 fw-semibold"
                            :class="form.status === 'CLOSED_WON' ? 'btn-c-success' : 'btn-c-primary'">
                            <span>
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else :class="isEdit ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"></i>
                                {{ submitTextButton }}
                            </span>
                        </button>

                    </div>
                </template>

            </modal>
        </div>
    </div>
</template>
<style scoped>
/* Memperhalus animasi masuknya form */
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

/* Modifikasi scrollbar agar cantik di area timeline */
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

/* Efek hover tombol */
.hover-bg-primary-subtle:hover {
    background-color: var(--bs-primary-bg-subtle) !important;
}
</style>
