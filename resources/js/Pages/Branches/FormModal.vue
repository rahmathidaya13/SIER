<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import axios from "axios";
const props = defineProps({
    idBranch: {
        type: String,
        default: null
    },
    show: Boolean,
});

const isLoading = ref(false);
const isEdit = computed(() => !!props.idBranch);
const branches = ref(null);
const form = useForm({
    name: '',
    address: '',
    isActive: 'active',
    status: 'unofficial',
    phones: [
        { phone: '' }
    ],
});
const loadBranch = async () => {
    isLoading.value = true;
    if (!props.idBranch) {
        resetForm();
        return
    }

    try {
        const { data } = await axios.get(`/branch/show/${props.idBranch}`);
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    } finally {
        isLoading.value = false;
    }
}
const fillForm = (data) => {
    form.defaults({
        name: data.name || '',
        address: data.address || '',
        isActive: data.is_active || 'active',
        status: data.status || 'unofficial',
        phones: data.branch_phone && data.branch_phone.length > 0
            ? data.branch_phone.map(p => ({ phone: p.phone })) : [{ phone: '' }],
    });
    branches.value = data;
    form.reset();
}
const resetForm = () => {
    form.defaults({
        name: '',
        address: '',
        isActive: 'active',
        status: 'unofficial',
        phones: [
            { phone: '' }
        ],
    })
    form.reset();
    form.clearErrors();
}
const submit = () => {
    const url = isEdit.value
        ? route('branch.update', props.idBranch)
        : route('branch.store');


    const transformData = isEdit.value
        ? (data) => ({ ...data, _method: 'put' })
        : (data) => data;

    form.transform(transformData).post(url, {
        preserveScroll: true,
        preserveState: true,
        replace: true,

        onSuccess: () => {
            close();
            resetForm();
        },

    });
};

watch(
    () => props.show,
    async (isOpen) => {
        if (!isOpen) return;
        if (props.idBranch) {
            await loadBranch();
        } else {
            resetForm();
        }

    }, {
    immediate: true
}
);
const emit = defineEmits(["update:show"]);
const close = () => {
    emit("update:show", false);
    resetForm();
}
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return isEdit.value ? 'Simpan Perubahan' : ' Simpan';
});
const addPhone = () => {
    form.phones.push({
        phone: "",
    })
}
const removePhone = (index) => {
    form.phones.splice(index, 1);
}
</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="600px" size="modal-lg" :icon="[
                'text-coffee-main',
                {
                    'bi bi-pencil-square text-primary': isEdit,
                    'bi bi-plus-circle-fill text-primary': !isEdit
                }
            ]" :show="props.show" :title="isEdit ? `Ubah Data Cabang ${branches?.name}` : 'Buat Cabang Baru'"
                :mobileFullscreen="false" @closed="close">

                <template #body>
                    <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

                    <div v-if="isLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-3 small fw-medium">Sedang mengambil data...</p>
                    </div>
                    <div v-else>
                        <form-wrapper @submit.prevent="submit">
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <input-label for="name_input" labelClass="fw-bold text-muted small mb-2"
                                            value="Nama Cabang" />
                                        <input-text placeholder="Contoh: Kantor Cabang Sudirman" name="name"
                                            v-model="form.name" :error="form.errors.name" />
                                        <input-error :message="form.errors.name" />
                                    </div>
                                    <div class="mb-3">
                                        <input-label labelClass="small mb-2 fw-bold text-muted" for="status"
                                            value="STATUS OPERASIONAL" />
                                        <select-input :error="form.errors.isActive" name="status"
                                            text="-- Pilih Status --" :options="[
                                                { value: 'active', label: '✅ Aktif / Beroperasi' },
                                                { value: 'inactive', label: '❌ Tidak Aktif / Tutup' }
                                            ]" v-model="form.isActive" />
                                        <input-error :message="form.errors.isActive" />
                                    </div>
                                    <div class="mb-3">
                                        <input-label labelClass="small mb-2 fw-bold text-muted" for="status_input"
                                            value="TIPE CABANG" />
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="px-3 p-2 rounded-4 border transition-all"
                                                :class="form.status === 'official' ? 'border-primary bg-primary bg-opacity-10 text-primary fw-bold' : 'text-muted bg-light'">
                                                <radio-button label="Official" v-model:checked="form.status"
                                                    value="official" name="status" />

                                            </div>
                                            <div class="px-2 p-2 rounded-4 border transition-all "
                                                :class="form.status === 'unofficial' ? 'border-primary bg-primary bg-opacity-10 text-primary fw-bold' : 'text-muted bg-light'">
                                                <radio-button label="Unofficial" v-model:checked="form.status"
                                                    value="unofficial" name="status" />
                                            </div>
                                        </div>
                                        <input-error :message="form.errors.status" />
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <input-label labelClass="small mb-0 fw-bold text-muted"
                                                value="NOMOR TELEPON CABANG" />
                                            <button type="button" class="btn btn-primary btn-sm py-1 fw-medium"
                                                @click="addPhone">
                                                <i class="bi bi-plus-circle me-1"></i> Tambah Nomor
                                            </button>
                                        </div>

                                        <div class="bg-light p-3 rounded-3 border overflow-auto"
                                            style="max-height: 250px;">
                                            <div v-for="(ph, index) in form.phones" :key="index" class="mb-2">
                                                <div class="d-flex justify-content-between gap-1">
                                                    <div class="position-relative w-100">
                                                        <i class="fas fa-phone-alt fs-7 input-icon-left"></i>
                                                        <input-number :error="form.errors[`phones.${index}.phone`]"
                                                            :tabindex="index + 1" v-model="ph.phone"
                                                            :name="`phones.${index}.phone`"
                                                            :placeholder="`Contoh: 0812xxxx (Nomor ${index + 1})`"
                                                            input-class="input-fixed-height" />
                                                    </div>
                                                    <button v-if="form.phones.length > 1" type="button"
                                                        class="btn btn-c-danger rounded-3"
                                                        @click.prevent="removePhone(index)" title="Hapus nomor ini">
                                                        <i class="fas fa-times-circle"></i>
                                                    </button>
                                                </div>
                                                <input-error :message="form.errors[`phones.${index}.phone`]"
                                                    class="mt-1" />
                                            </div>
                                            <div v-if="form.phones.length === 0"
                                                class="text-center text-muted small py-3 border border-dashed rounded-2 bg-white mt-2">
                                                <i class="fas fa-phone-slash mb-2 fs-5 opacity-50"></i><br>
                                                Belum ada nomor telepon. Silakan klik tambah.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <input-label labelClass="small mb-2 fw-bold text-muted" for="address"
                                            value="ALAMAT LENGKAP" />
                                        <text-area :error="form.errors.address" name="address" v-model="form.address"
                                            :rows="4" placeholder="Masukkan alamat yang sesuai..." />
                                        <input-error :message="form.errors.address" />
                                    </div>
                                </div>
                            </div>
                        </form-wrapper>
                    </div>

                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4"
                            @click.prevent="close">Batal</button>
                        <button @click.prevent="submit" type="button" :disabled="form.processing"
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
