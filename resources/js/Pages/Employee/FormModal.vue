<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import axios from "axios";
const props = defineProps({
    idUser: String,
    show: Boolean,
    isLoading: Boolean,
    branches: Array,
    permissions: Array
});


const isDeveloper = hasRole('developer');
const isSuperadmin = hasRole('super_admin');
const isEdit = computed(() => !!props.idUser);
const isLoading = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: null, // Default
    id_branch: null,
    is_active: 'active',
    permissions: []
});

const fillForm = (data) => {
    console.log(data);
    form.defaults({
        name: data.name,
        email: data.email,
        role: data.roles[0]?.name,
        id_branch: data.id_branch,
        is_active: data.is_active,
        permissions: data.permissions ? data.permissions.map(p => p.id) : [],
    });
    form.reset();
    form.clearErrors();
}

const resetForm = () => {
    form.defaults({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: null,
        id_branch: null,
        is_active: 'active',
        permissions: [],
    });
    form.reset();
    form.clearErrors();
}
const loadUser = async () => {
    if (!props.idUser) {
        resetForm();
        return
    }
    isLoading.value = true
    try {
        const { data } = await axios.get(`/employee/show/${props.idUser}`);
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    } finally {
        isLoading.value = false
    }
}

const branchOptions = computed(() => [
    ...props.branches.map(data => ({
        label: data.name,
        value: data.id_branch,
    }))
]);

const permissionOptions = computed(() => [
    ...props.permissions.map(data => ({
        label: data.name,
        value: data.id,
    }))
]);
console.log(permissionOptions.value);
const availableRoles = computed(() => {
    if (isDeveloper) {
        return [
            { label: 'Super Admin', value: 'super_admin' },
            { label: 'Supervisor', value: 'supervisor' },
            { label: 'Admin', value: 'admin' },
            { label: 'Sales', value: 'sales' },
            { label: 'Teknisi', value: 'teknisi' },
        ]
    }
    if (isSuperadmin) {
        return [
            { label: 'Supervisor', value: 'supervisor' },
            { label: 'Admin', value: 'admin' },
            { label: 'Sales', value: 'sales' },
            { label: 'Teknisi', value: 'teknisi' },
        ]
    }
    return [
        { label: 'Admin', value: 'admin' },
        { label: 'Sales', value: 'sales' },
        { label: 'Teknisi', value: 'teknisi' },
    ]
});
const submit = () => {
    const url = isEdit.value
        ? route('employee.update', props.idUser)
        : route('employee.store');

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
            resetForm();
        },
        onError: () => {
            form.password = '';
        }
    });
};
watch(
    () => props.show,
    async (isOpen) => {
        if (!isOpen) return;
        if (props.idUser) {
            await loadUser();
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
    resetForm()
}
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return isEdit.value ? 'Simpan Perubahan' : ' Simpan Data';
});
const generateRandomPassword = () => {
    const randomPass = 'VILB-' + Math.floor(1000 + Math.random() * 9000);
    form.password = randomPass;
    form.password_confirmation = randomPass;
};
const showPassword = ref(false);
const togglePasswordVisibilty = () => {
    showPassword.value = !showPassword.value;
};

const moduleTranslations = {
    'product': 'Katalog Produk',
    'lead': 'Data Prospek (Lead)',
    'closing': 'Rekap Closing',
    'channel': 'Trafik Channel',
    'users': 'Manajemen Pegawai',
    'export': 'Laporan & Rekap'
    // Silakan tambah/ubah modul lain sesuai kebutuhan aplikasi Anda di sini
};
// Mengelompokkan permissions menjadi format Matrix (Baris = Modul, Kolom = Aksi)
const permissionMatrix = computed(() => {
    const matrix = {};

    props.permissions.forEach(perm => {
        // Memecah "product-create" menjadi module="product" dan action="create"
        // Jika nama permission Anda menggunakan underscore, ganti split('-') menjadi split('_')
        const [module, ...actionParts] = perm.name.split('-');
        const action = actionParts.join('-');

        if (!matrix[module]) {
            matrix[module] = {
                name: moduleTranslations[module] || module.replace(/-/g, ' '),
                view: null,
                create: null,
                edit: null,
                delete: null,
                data: null // ini digunakan untuk export-data
            };
        }

        // Klasifikasikan ke dalam kolom yang tepat
        if (['view', 'create', 'edit', 'delete', 'data'].includes(action)) {
            matrix[module][action] = perm;
        }
    });

    return Object.values(matrix);
});
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
            ]" :show="props.show" :title="isEdit ? 'Ubah Data Pegawai' : 'Tambah Data Pegawai'"
                :subTitle="isEdit ? 'Perbarui informasi pegawai' : 'Tambahkan informasi pegawai baru'"
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

                            <div class="mb-3">
                                <input-label labelClass="mb-1" for="name_input" value="Nama Lengkap" />
                                <input-text :error="form.errors.name" v-model="form.name" name="name"
                                    placeholder="Cth: Dimas Anggara" />
                                <input-error :message="form.errors.name" />
                            </div>

                            <div class="mb-3">
                                <input-label labelClass="mb-1" for="email_input" value="Email" />
                                <input-text :disabled="isEdit" name="email" :error="form.errors.email"
                                    v-model="form.email" type="email" placeholder="dimas@perusahaan.com" />
                                <small class="text-muted" style="font-size:12px;">*Email yang digunakan harus yang
                                    aktif</small>
                                <input-error :message="form.errors.email" />
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <input-label labelClass="mb-1" for="roles_select" value="Posisi / Jabatan" />
                                    <select-input name="roles" :error="form.errors.role" v-model="form.role"
                                        text="-- Pilih Jabatan --" :options="availableRoles" />
                                    <input-error :message="form.errors.role" />
                                </div>

                                <div class="col-6">
                                    <input-label labelClass="mb-1" for="branch_select" value="Penempatan Cabang" />
                                    <select-input :error="form.errors.id_branch" v-model="form.id_branch"
                                        text="-- Pilih Cabang --" name="branch" :options="branchOptions" />
                                    <input-error :message="form.errors.id_branch" />
                                </div>
                            </div>
                            <div class="mb-3 bg-light p-3 rounded-3 border">
                                <input-label labelClass="mb-2" for="is_active" value="Status Pegawai" />
                                <div class="d-flex gap-5">
                                    <radio-button name="is_active" v-model:checked="form.is_active" value="active"
                                        label="Aktif (Akses diberikan)" />
                                    <radio-button name="is_active" v-model:checked="form.is_active" value="inactive"
                                        label="Tidak Aktif (Akses ditangguhkan)" />
                                </div>
                                <input-error :message="form.errors.phone" />
                            </div>

                            <div class="mb-3">
                                <input-label labelClass="mb-1" value="Hak Akses Khusus (Permissions)" />
                                <p class="text-muted mb-2" style="font-size: 12.5px;">
                                    *Pilih hak akses
                                    <span v-if="!form.role" class="text-warning"><i
                                            class="bi bi-info-circle me-1"></i>Pilih jabatan terlebih dahulu untuk
                                        melihat akses dasarnya.</span>
                                </p>
                                <div class="table-responsive border rounded-3 mt-1 custom-scrollbar"
                                    style="max-height: 380px;">
                                    <table class="table table-sm table-hover mb-0 align-middle"
                                        style="font-size: 13.5px;">
                                        <thead class="table-light text-center sticky-top ">
                                            <tr>
                                                <th scope="col" class="text-start">Modul Sistem</th>
                                                <th scope="col" class="py-2">Lihat (View)</th>
                                                <th scope="col" class="py-2">Tambah (Create)</th>
                                                <th scope="col" class="py-2">Ubah (Edit)</th>
                                                <th scope="col" class="py-2">Hapus (Delete)</th>
                                                <th scope="col" class="py-2">Export Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Looping per modul -->
                                            <tr v-for="row in permissionMatrix" :key="row.name">
                                                <!-- Nama Modul -->
                                                <td class="text-capitalize fw-semibold text-start ps-3 bg-light">
                                                    {{ row.name }}
                                                </td>

                                                <!-- Kolom View -->
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-flex justify-content-center">
                                                        <input v-if="row.view" type="checkbox"
                                                            class="form-check-input cursor-pointer" role="switch"
                                                            :value="row.view.id" v-model="form.permissions"
                                                            style="transform: scale(1.4);">
                                                        <span v-else class="text-muted small">-</span>
                                                    </div>
                                                </td>

                                                <!-- Kolom Create -->
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-flex justify-content-center">
                                                        <input v-if="row.create" type="checkbox" role="switch"
                                                            class="form-check-input cursor-pointer"
                                                            :value="row.create.id" v-model="form.permissions"
                                                            style="transform: scale(1.4);">
                                                        <span v-else class="text-muted small">-</span>
                                                    </div>
                                                </td>

                                                <!-- Kolom Edit -->
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-flex justify-content-center">
                                                        <input v-if="row.edit" type="checkbox" role="switch"
                                                            class="form-check-input cursor-pointer" :value="row.edit.id"
                                                            v-model="form.permissions" style="transform: scale(1.4);">
                                                        <span v-else class="text-muted small">-</span>
                                                    </div>
                                                </td>

                                                <!-- Kolom Delete -->
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-flex justify-content-center">
                                                        <input v-if="row.delete" type="checkbox" role="switch"
                                                            class="form-check-input cursor-pointer"
                                                            :value="row.delete.id" v-model="form.permissions"
                                                            style="transform: scale(1.4);">
                                                        <span v-else class="text-muted small">-</span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-flex justify-content-center">
                                                        <input v-if="row.data" type="checkbox" role="switch"
                                                            class="form-check-input cursor-pointer" :value="row.data.id"
                                                            v-model="form.permissions" style="transform: scale(1.4);">
                                                        <span v-else class="text-muted small">-</span>
                                                    </div>
                                                </td>

                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <input-error :message="form.errors.permissions" class="mt-1" />

                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <input-label for="password_input" labelClass="mb-1" value="Kata Sandi Akses" />
                                    <button v-if="!isEdit" type="button" @click="generateRandomPassword"
                                        class="btn btn-sm btn-link text-decoration-none py-0 small">
                                        <i class="bi bi-magic me-1"></i>Buat Otomatis
                                    </button>
                                </div>
                                <div class="position-relative">
                                    <input-text name="password" :disabled="isEdit"
                                        inputClass="input-fixed-height has-icon-right input-icon-left-none"
                                        :error="form.errors.password" v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'" placeholder="••••••••" />
                                    <i @click="togglePasswordVisibilty" :class="[
                                        'input-icon-right',
                                        'cursor-pointer',
                                        showPassword
                                            ? 'bi bi-eye-slash-fill'
                                            : 'bi bi-eye-fill',
                                    ]"></i>
                                </div>
                                <small class="text-muted" style="font-size:12px;">*Gunakan minimal 8 karakter. Pegawai
                                    dapat menggantinya nanti.</small>
                                <input-error :message="form.errors.password" />
                            </div>
                        </form-wrapper>
                    </div>

                </template>

                <template #footer>
                    <div v-if="!isLoading" class="d-flex justify-content-end gap-2 p-2 bg-transparent">
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
<style scoped>
/* Agar scrollbar terlihat modern di Windows/Chrome */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.cursor-pointer {
    cursor: pointer;
}

.form-check-input:checked {
    background-color: var(--c-success);
    border-color: var(--c-success-subtle);
}
</style>
