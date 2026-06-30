<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import { useRowAlert } from '@/Composables/useAlert';
const { show, __alert, hide } = useRowAlert();
import axios from "axios";
const props = defineProps({
    idLead: {
        type: String,
        default: null
    },
    show: Boolean,
    channels: Array,
    products: Array
});
const isEdit = computed(() => !!props.idLead);
const isChangingProduct = ref(false);
const isLoading = ref(false);
const dataLead = ref(null);

// 1. STATE SAKELAR TAB
const activeTab = ref('prospect');
const tabList = computed(() => {
    if (isEdit.value) {
        return [
            {
                id: 'activity',
                name: '💬 Aktivitas Follow-up',
                badge: dataLead.value?.activity?.length || 0
            },
            {
                id: 'prospect',
                name: '📋 Data Prospek'
            }
        ];
    }
    return [
        { id: 'prospect', name: '📋 Data Prospek' },
        { id: 'activity', name: '💬 Aktivitas Follow-up' }
    ];
})

const form = useForm({
    name: '',
    phone: '',
    email: '',
    id_channel: null,
    id_product: '',
    temperature: null,
    status: null,
    follow_up_date: '',
    notes: '',
    product_name: '',
    new_activity: '',
    role_name: '',
});
const fillForm = (data) => {
    console.log(data);
    form.defaults({
        name: data.name,
        phone: data.phone,
        email: data.email,
        id_channel: data.id_channel,
        id_product: data.id_product,
        temperature: data.temperature,
        status: data.status,
        follow_up_date: data.follow_up_date || '',
        notes: data.notes,
        product_name: data.product?.product_name || '',
        new_activity: '',
        // role_name: data.user?.roles[0].name ?? 'Sistem'
    })
    dataLead.value = data
    form.reset();
}
const resetForm = () => {
    form.defaults({
        name: '',
        phone: '',
        email: '',
        id_channel: null,
        id_product: '',
        temperature: null,
        status: null,
        follow_up_date: '',
        notes: '',
        product_name: '',
        new_activity: '',
        role_name: ''
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
        activeTab.value = 'activity';
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    }
    finally {
        isLoading.value = false
    }

}

const channelOptions = computed(() => [
    ...props.channels.map(data => ({
        label: data.name,
        value: data.id_channel,
    }))
]);

const selectedProduct = {
    width: '100%',
    placeholder: 'Ketik nama produk...',
    allowClear: true,
    minimumInputLength: 2,
    ajax: {
        url: route('product.search'), // Arahkan ke endpoint controller Anda
        dataType: 'json',
        delay: 300, // Tunggu 300ms setelah user berhenti mengetik baru request ke backend (Debounce)
        data: function (params) {
            return {
                keyword: params.term // Kata kunci yang diketik user dikirim sebagai ?keyword=...
            };
        },
        processResults: function (data) {
            // Mapping respon backend menjadi format {id, text} yang dipahami Select2
            return {
                results: data.map(item => ({
                    id: item.id_product,
                    text: item.name,
                    product_name: item.name
                }))
            };
        }
    }
}
const onProductSelected = (selectedOption) => {
    form.product_name = selectedOption?.product_name || '';
};
const submit = () => {
    const url = isEdit.value
        ? route('leads.update', props.idLead)
        : route('leads.store');

    // Jika Edit, Laravel butuh trik method spoofing (_method: put) untuk upload file
    const transformData = isEdit.value
        ? (data) => ({ ...data, _method: 'put' })
        : (data) => data;

    form.transform(transformData).post(url, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            close();
            form.reset();
        },
        onError: (errors) => {
            const prospectFields = ['name', 'phone', 'email', 'id_channel', 'id_product', 'temperature', 'status', 'follow_up_date', 'notes'];
            const isErrorInProspect = Object.keys(errors).some(field => prospectFields.includes(field));

            if (isErrorInProspect && activeTab.value !== 'prospect') {
                activeTab.value = 'prospect';
            } else if (errors.new_activity && activeTab.value !== 'activity') {
                activeTab.value = 'activity';
            }
        }
    });
};
watch(
    () => props.show,
    async (isOpen) => {
        if (!isOpen) return;
        if (props.idLead) {
            await loadLead();
        } else {
            resetForm();
            activeTab.value = 'prospect'
        }

    }, {
    immediate: true
}
);
const emit = defineEmits(["update:show"]);
const close = () => {
    emit("update:show", false);
    resetForm();
    isChangingProduct.value = false;
}
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return isEdit.value ? 'Menyimpan...' : 'Simpan Prospek';
});
const getRoleName = (data) => {
    const role = data.user.roles.map(role => role.name)
    console.log(role);
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

const activityEdit = reactive({
    id: null,
    new_value: '',
});

const editActivity = (activities) => {
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
            ]" :show="props.show" :title="isEdit ? 'Manajemen Prospek & Follow-Up' : ' Input Prospek Baru'"
                :mobileFullscreen="false" @closed="close" :isLoading="isLoading">

                <template #body>

                    <div v-if="form.errors.unauthorized_action"
                        class="alert alert-c-danger d-flex justify-content-between align-items-center" role="alert">
                        <div class="d-flex align-items-center gap-2 fw-semibold">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ form.errors.unauthorized_action }}
                        </div>
                        <button @click="form.clearErrors('unauthorized_action')" type="button"
                            class="bg-transparent rounded-circle border-0 fs-4">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <div class="celestial-tab-container mb-4">
                        <button v-for="tab in tabList" :key="tab.id" type="button" @click="activeTab = tab.id"
                            class="celestial-tab-btn" :class="{ 'active': activeTab === tab.id }">
                            <span>{{ tab.name }}</span>
                            <span v-if="tab.badge" class="badge rounded-pill bg-primary ms-2"
                                style="font-size: 10px;">{{ tab.badge }}</span>
                        </button>
                    </div>

                    <form-wrapper @submit.prevent="submit">
                        <div v-show="activeTab === 'prospect'" class="row g-3 animate-fade-in">
                            <div class="col-md-6">
                                <label for="name_input" class="form-label small fw-bold text-muted">NAMA PROSPEK
                                    *</label>
                                <input-text :error="form.errors.name" v-model="form.name" name="name"
                                    placeholder="Cth: Budi Santoso" />
                                <input-error :message="form.errors.name" />
                            </div>

                            <div class="col-md-6">
                                <label for="phone_input" class="form-label small fw-bold text-muted">NO. WHATSAPP
                                    *</label>
                                <input-number :maxChar="15" :error="form.errors.phone" v-model="form.phone" name="phone"
                                    placeholder="081233445566" />
                                <input-error :message="form.errors.phone" />
                            </div>

                            <div class="col-md-6">
                                <label for="email_input" class="form-label small fw-bold text-muted">EMAIL
                                    PELANGGAN (Opsional)</label>
                                <input-text :error="form.errors.email" v-model="form.email" name="email"
                                    placeholder="Cth: dimas@perusahaan.com" />
                                <input-error :message="form.errors.email" />
                            </div>

                            <div class="col-md-6">
                                <label for="channels_select" class="form-label small fw-bold text-muted">SUMBER
                                    TRAFIK
                                    (CHANNEL) *</label>
                                <select-input text="-- Pilih Channel --" name="channels" v-model="form.id_channel"
                                    :error="form.errors.id_channel" :options="channelOptions" />
                                <input-error :message="form.errors.id_channel" />
                            </div>

                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <label for="product_select" class="form-label small fw-bold text-muted">PRODUK
                                        YANG
                                        DIMINATI</label>

                                    <span v-if="form.id_product && !isChangingProduct" @click="isChangingProduct = true"
                                        class="small fw-bold text-primary"
                                        style="cursor: pointer; text-decoration: none;">
                                        Ganti produk
                                    </span>

                                    <span v-if="isChangingProduct && dataLead?.id_product"
                                        @click="isChangingProduct = false" class="small fw-bold text-danger"
                                        style="cursor: pointer;">
                                        Batal
                                    </span>
                                </div>

                                <select-2 v-if="!form.id_product || isChangingProduct" :error="form.errors.id_product"
                                    @select="onProductSelected" :settings="selectedProduct" name="product"
                                    v-model="form.id_product" />

                                <input-text v-else inputClass="text-danger fw-bold" name="products"
                                    v-model="form.product_name" :disabled="true" />

                                <input-error :message="form.errors.id_product" />
                            </div>

                            <div class="col-md-4">
                                <label for="temp_select" class="form-label small fw-bold text-muted">SUHU
                                    KETERTARIKAN</label>
                                <select-input text="-- Pilih Suhu --" name="temp" v-model="form.temperature"
                                    :error="form.errors.temperature"
                                    :selectClass="{ 'text-danger': form.temperature === 'COLD', 'text-warning': form.temperature === 'WARM', 'text-success': form.temperature === 'HOT' }"
                                    :options="[
                                        { label: '🔥 HOT (Siap Bayar)', value: 'HOT' },
                                        { label: '⏳ WARM (Tanya-tanya)', value: 'WARM' },
                                        { label: '❄️ COLD (Sekedar Lewat)', value: 'COLD' },
                                    ]" />
                                <input-error :message="form.errors.temperature" />
                            </div>

                            <div class="col-md-4">
                                <label for="status_select" class="form-label small fw-bold text-muted">STATUS
                                    PROSPEK</label>
                                <select-input text="-- Pilih Status --" v-model="form.status" name="status"
                                    :error="form.errors.status" :disabled="!form.status === 'CLOSED_WON'" :options="[
                                        { label: '⭐ Baru Masuk', value: 'NEW' },
                                        { label: '💬 Sedang Follow Up', value: 'FOLLOW_UP' },
                                        { label: '✖ Lost / Batal', value: 'CLOSED_LOST' },
                                        { label: '✔ Closing (Terjual)', value: 'CLOSED_WON' },
                                    ]" />
                                <input-error :message="form.errors.status" />
                            </div>

                            <div class="col-md-4">
                                <label for="follow_up_date_input" class="form-label small fw-bold text-muted">JADWAL
                                    FOLLOW UP</label>
                                <input-text name="follow_up_date" v-model="form.follow_up_date" type="date" />
                            </div>

                            <div class="col-12">
                                <label for="notes_input" class="form-label small fw-bold text-muted">INFORMASI /
                                    STICKY NOTE (Opsional)</label>
                                <text-area :error="form.errors.notes" name="notes" v-model="form.notes" :rows="3"
                                    placeholder="Tuliskan catatan opsional disini..." />
                                <input-error :message="form.errors.notes" />
                            </div>
                        </div>

                        <div v-show="activeTab === 'activity'" class="row g-3 animate-fade-in">
                            <div class="col-12">
                                <label for="activity_input" class="form-label small fw-bold text-muted">LAPORKAN
                                    HASIL
                                    FOLLOW-UP HARI INI</label>
                                <text-area :maxChar="2500" :error="form.errors.new_activity" name="activity"
                                    v-model="form.new_activity" :rows="3"
                                    placeholder="Tuliskan apa yang baru saja dibicarakan dengan pelanggan di sini..." />
                                <input-error :message="form.errors.new_activity" />
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                    *Menyimpan aktivitas baru akan memperbarui tanggal kontak terakhir pelanggan
                                    ini.
                                </small>
                            </div>

                            <div v-if="dataLead?.activity && dataLead?.activity.length > 0"
                                class="col-12 mt-4 pt-3 border-top">
                                <label class="form-label small fw-bold text-muted mb-3 d-block"
                                    style="letter-spacing: 0.5px;">
                                    🕒 JEJAK REKAM KONSUMEN (ACTIVITY TIMELINE):
                                </label>

                                <div class="ps-1 pe-2 position-relative" style="max-height: 440px; overflow-y: auto;">

                                    <div class="d-flex gap-2 mb-3 position-relative" v-for="act in dataLead.activity"
                                        :key="act.id_lead_activity">

                                        <div class="timeline-line"></div>

                                        <div class="badge bg-primary-subtle text-primary rounded-circle p-0 d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm"
                                            style="width: 34px; height: 34px; z-index: 2;">
                                            <i class="bi bi-person-fill fs-6"></i>
                                        </div>

                                        <div class="d-flex flex-column flex-grow-1">

                                            <div v-if="__alert.visible && __alert.id === act.id_lead_activity"
                                                class="alert py-2 px-3 mb-2" :class="`alert-${__alert.type}`">
                                                <i :class="{
                                                    'bi bi-check-circle-fill': __alert.type === 'success',
                                                    'bi bi-exclamation-triangle-fill': __alert.type === 'warning',
                                                    'bi bi-info-circle-fill': __alert.type === 'info',
                                                    'bi bi-x-circle-fill': __alert.type === 'danger'
                                                }" class="me-2"></i>
                                                <span class="small">
                                                    {{ __alert.message }}
                                                </span>
                                            </div>


                                            <div class="bg-light p-2 px-3 rounded-3 border flex-grow-1 shadow-sm">

                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-1 border-bottom pb-1">
                                                    <div class="d-flex flex-column small">
                                                        <div class="fw-bold text-dark text-capitalize"
                                                            style="font-size: 14px;">
                                                            <i class="bi bi-plus-circle"></i>
                                                            Dibuat: {{ act.user?.activity_creator_name || 'Sistem'
                                                            }} -
                                                            {{ getRoleName(act).label }}
                                                        </div>
                                                        <!-- <div class="fw-bold text-dark text-capitalize"
                                                            style="font-size: 14px;">
                                                            <i class="bi bi-pencil-square"></i>
                                                            Diubah: {{ act.user?.activity_creator_name || 'Sistem'
                                                            }} -
                                                            {{ getRoleName(act).label }}
                                                        </div> -->
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-muted" style="font-size: 12px;">
                                                            <i class="bi bi-plus-circle"></i>
                                                            {{ new Date(act.created_at).toLocaleString('id-ID',
                                                                {
                                                                    day: 'numeric', month: 'short', hour: '2-digit',
                                                                    minute: '2-digit'
                                                                }) }}
                                                        </span>
                                                        <span class="text-muted" style="font-size: 12px;">
                                                            <i class="bi bi-pencil-square"></i>
                                                            {{ new Date(act.updated_at).toLocaleString('id-ID',
                                                                {
                                                                    day: 'numeric', month: 'short', hour: '2-digit',
                                                                    minute: '2-digit'
                                                                }) }}
                                                        </span>

                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between flex-column">

                                                    <p v-if="!activityEdit.id" class="mb-3 mt-1"
                                                        style="font-size: 14px; white-space: pre-line;">
                                                        {{ act.description
                                                        }}</p>
                                                    <div class="mb-2">
                                                        <text-area
                                                            :error="__alert.visible && __alert.id === act.id_lead_activity"
                                                            v-if="activityEdit.id === act.id_lead_activity"
                                                            :maxChar="2500" name="activity" inputClass="bg-transparent"
                                                            v-model="activityEdit.new_value" :rows="3"
                                                            placeholder="Tuliskan apa yang baru saja dibicarakan dengan pelanggan di sini..." />
                                                    </div>
                                                    <div class="d-flex gap-2 align-items-center">
                                                        <span
                                                            @click.prevent="activityEdit.id === act.id_lead_activity ? updateActivity() : editActivity(act)"
                                                            class="text-primary cursor-pointer badge badge-soft-success">
                                                            {{ activityEdit.id === act.id_lead_activity ? 'Simpan' :
                                                                'Ubah'
                                                            }}
                                                        </span>
                                                        <span v-if="activityEdit.id === act.id_lead_activity"
                                                            @click.prevent="cancelEditActivity"
                                                            class="badge badge-soft-danger cursor-pointer">Batal</span>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div v-else class="col-12 text-center py-4 text-muted">
                                <i class="bi bi-chat-square-dots fs-1 opacity-25 d-block mb-1"></i>
                                <small>Belum ada riwayat percakapan pada prospek ini.</small>
                            </div>
                        </div>

                    </form-wrapper>



                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4"
                            @click.prevent="close">Batal</button>
                        <button @click.prevent="submit" type="button" :disabled="form.processing || isLoading"
                            class="btn btn-height-1 fw-semibold" :class="isEdit ? 'btn-c-success' : 'btn-c-primary'">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else :class="isEdit ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"></i>
                            {{ submitTextButton }}
                        </button>
                    </div>
                </template>

            </modal>
        </div>
    </div>
</template>
<style scoped>
.timeline-line {
    position: absolute;
    left: 16px;
    top: 34px;
    bottom: -20px;
    width: 2px;
    background-color: #cbd5e1;
    z-index: 1;
}

/* DESAIN SWITCHER TABS MEWAH ALA APPLE / LINEAR */
.celestial-tab-container {
    display: flex;
    background-color: #f1f5f9;
    /* Slate-100 */
    padding: 5px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.celestial-tab-btn {
    flex: 1;
    border: none;
    background: transparent;
    padding: 10px 15px;
    border-radius: 9px;
    font-size: 0.885rem;
    font-weight: 600;
    color: #64748b;
    /* Slate-500 */
    transition: all 0.2s ease-in-out;
    display: flex;
    align-items: center;
    justify-content: center;
}

.celestial-tab-btn:hover {
    color: #1e293b;
}

.celestial-tab-btn.active {
    background-color: #ffffff;
    color: var(--c-primary);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.06);
}
</style>
