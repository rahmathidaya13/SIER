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
    permissions: []
});

const fillForm = (data) => {
    console.log(data);
    form.defaults({
        name: data.name,
        email: data.email,
        role: data.roles[0]?.name,
        id_branch: data.id_branch,
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

const allowedForSuperAdmin = [
    'customer-view', 'customer-create', 'customer-edit', 'customer-delete',
    'channel-view', 'channel-create', 'channel-edit', 'channel-delete',
    'product-view', 'product-create', 'product-edit', 'product-delete',
    'lead-view', 'lead-create', 'lead-edit', 'lead-delete','lead-convert',
    'closing-view', 'closing-create', 'closing-edit', 'closing-delete','closing-remaining','closing-payment_cancel',
]


const filteredPermissions = computed(() => {
    if (isDeveloper) return props.permissions;
    if (isSuperadmin) {
        return props.permissions.filter(perm => allowedForSuperAdmin.includes(perm.name));
    }
    // if (isSupervisor) {
    //     return props.permissions.filter(perm => allowedForSpv.includes(perm.name));
    // }

    return [];
})
const categoryTranslations = {
    'branch': 'Data Cabang',
    'customer': 'Data Pelanggan',
    'channel': 'Sumber Trafik (Channel)',
    'product': 'Data Produk',
    'lead': 'Data Prospek (Leads)',
    'closing': 'Rekap Closing',
    'users': 'Manajemen Pengguna',
    'data': 'Ekspor Data',
};
const permissionTranslations = {

    'branch-view': 'Diizinkan melihat daftar cabang',
    'branch-create': 'Diizinkan menambah cabang baru',
    'branch-edit': 'Diizinkan mengubah data cabang',
    'branch-delete': 'Diizinkan menghapus cabang',

    // Customer
    'customer-view': 'Diizinkan melihat daftar pelanggan',
    'customer-create': 'Diizinkan menambah pelanggan baru',
    'customer-edit': 'Diizinkan mengubah data pelanggan',
    'customer-delete': 'Diizinkan menghapus pelanggan',

    // Channel
    'channel-view': 'Diizinkan melihat daftar channel',
    'channel-create': 'Diizinkan menambah channel baru',
    'channel-edit': 'Diizinkan mengubah data channel',
    'channel-delete': 'Diizinkan menghapus channel',

    // Produk
    'product-view': 'Diizinkan melihat daftar produk',
    'product-create': 'Diizinkan menambah produk baru',
    'product-edit': 'Diizinkan mengubah data produk',
    'product-delete': 'Diizinkan menghapus produk',

    // Lead
    'lead-view': 'Diizinkan melihat data prospek',
    'lead-create': 'Diizinkan membuat prospek baru',
    'lead-edit': 'Diizinkan mengubah data prospek',
    'lead-delete': 'Diizinkan menghapus data prospek',
    'lead-convert': 'Diizinkan mengonversi status prospek',

    // Rekap Closing
    'closing-view': 'Diizinkan melihat data Closing',
    'closing-create': 'Diizinkan membuat Closing',
    'closing-edit': 'Diizinkan mengubah Closing',
    'closing-delete': 'Diizinkan menghapus Closing',
    'closing-remaining': 'Diizinkan melakukan pembayaran Pelunasan',
    'closing-payment_cancel': 'Diizinkan membatalkan pembayaran',

    'data-export': 'Diizinkan ekspor data',

    'users-view': 'Diizinkan melihat daftar pengguna',
    'users-create': 'Diizinkan menambah pengguna baru',
    'users-edit': 'Diizinkan mengubah data pengguna',
    'users-delete': 'Diizinkan menghapus pengguna',

};
const groupedPermissions = computed(() => {
    const groups = {};

    filteredPermissions.value.forEach(perm => {
        // Ambil kata pertama (contoh: "product" dari "product-create")
        const categoryKey = perm.name.split('-')[0];

        if (!groups[categoryKey]) {
            groups[categoryKey] = [];
        }

        groups[categoryKey].push({
            originalName: perm.name,
            label: permissionTranslations[perm.name] || perm.name,
            value: perm.id
        });
    });
    return Object.keys(groups).map(key => {

        const actionOrder = ['view', 'create', 'edit', 'delete', 'export'];

        const sortedPerms = groups[key].sort((a, b) => {
            const actionA = a.originalName.replace(`${key}-`, '');
            const actionB = b.originalName.replace(`${key}-`, '');

            let indexA = actionOrder.indexOf(actionA);
            let indexB = actionOrder.indexOf(actionB);

            if (indexA === -1) indexA = 99;
            if (indexB === -1) indexB = 99;

            return indexA - indexB;
        });

        return {
            key: key,
            title: categoryTranslations[key] || key,
            permissions: sortedPerms
        };
    });
});

</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="850px" size="modal-lg" :icon="[
                'text-coffee-main',
                {
                    'bi bi-pencil-square text-primary': isEdit,
                    'bi bi-plus-circle-fill text-primary': !isEdit
                }
            ]" :show="props.show" :title="isEdit ? 'Ubah Data Pegawai' : 'Tambah Data Pegawai'"
                :mobileFullscreen="true" @closed="close">

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
                                <input-text :disabled="isEdit" :error="form.errors.name" v-model="form.name" name="name"
                                    placeholder="Cth: Dimas Anggara" />
                                <input-error :message="form.errors.name" />
                            </div>

                            <div class="mb-3">
                                <input-label labelClass="mb-1" for="email_input" value="Email" />
                                <input-text :disabled="isEdit" name="email" :error="form.errors.email"
                                    v-model="form.email" type="email" placeholder="dimas@perusahaan.com" />
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
                            <!-- <div class="mb-3 bg-light p-3 rounded-3 border">
                                <input-label labelClass="mb-2" for="is_active" value="Status Pegawai" />
                                <div class="d-flex gap-5">
                                    <radio-button name="is_active" v-model:checked="form.is_active" value="active"
                                        label="Aktif (Akses diberikan)" />
                                    <radio-button name="is_active" v-model:checked="form.is_active" value="inactive"
                                        label="Tidak Aktif (Akses ditangguhkan)" />
                                </div>
                                <input-error :message="form.errors.phone" />
                            </div> -->

                            <div class="mb-3">
                                <input-label labelClass="mb-1" value="Hak Akses (Permissions)" />
                                <p class="text-muted mb-2" style="font-size: 12.5px;">
                                    <span v-if="!form.role" class="text-warning"><i
                                            class="bi bi-info-circle me-1"></i>Pilih jabatan terlebih dahulu untuk
                                        melihat akses dasarnya.</span>
                                </p>

                                <div class="border rounded-3 p-3 bg-light custom-scrollbar"
                                    style="max-height: 450px; overflow-y: auto;">
                                    <div v-for="group in groupedPermissions" :key="group.key" class="mb-4">

                                        <h6 class="text-capitalize text-muted mb-3 border-bottom pb-2"
                                            style="font-size: 13.5px; font-weight: 600;">
                                            {{ group.title }}
                                        </h6>

                                        <div class="row g-2">
                                            <div class="col-12 col-lg-6 col-xl-6" v-for="perm in group.permissions"
                                                :key="perm.value">
                                                <div class="form-check form-switch d-flex align-items-start mb-0">
                                                    <input class="form-check-input cursor-pointer me-3 flex-shrink-0"
                                                        :class="{ 'is-invalid': form.errors.permissions, 'is-valid': !form.errors.permissions && form.permissions.includes(perm.value) }"
                                                        type="checkbox" :id="'perm_' + perm.value" :value="perm.value"
                                                        v-model="form.permissions"
                                                        style="transform: scale(1.3); margin-top: 0;">

                                                    <label class="form-check-label cursor-pointer fw-medium"
                                                        :for="'perm_' + perm.value"
                                                        style="font-size: 13.5px; line-height: 1.4;">
                                                        {{ perm.label }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <input-error :message="form.errors.permissions" class="mt-1" />
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
