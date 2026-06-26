<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
const props = defineProps({
    data: Object,
    show: Boolean,
    isLoading: Boolean,
    branches: Array,
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const isSuperadmin = hasRole('super_admin');
const isEdit = computed(() => !!props.data?.id);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: null, // Default
    id_branch: isSuperadmin ? null : currentUser.value.id_branch,
});

const branchOptions = computed(() => [
    ...props.branches.map(data => ({
        label: data.name,
        value: data.id_branch,
    }))
]);
const availableRoles = computed(() => {
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
        ? route('employee.update', props.data?.id)
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
            form.reset();
        },
        onError: () => {
            form.password = '';
        }
    });
};
// const previewImage = ref(null)
// const fileName = ref("")
// const onFileChange = (e) => {
//     let file = e.target.files[0] || null
//     if (file && file.type.startsWith("image/")) {
//         fileName.value = file.name
//         previewImage.value = URL.createObjectURL(file)
//         form.image_upload = file
//     } else {
//         previewUrl.value = null;
//         fileName.value = null;
//         form.image_upload = null
//     }
// };

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            // Modal sedang DIBUKA
            if (isEdit.value) {
                form.name = props.data.name;
                form.email = props.data.email;
                form.role = props.data.role_name;
                form.id_branch = props.data.id_branch;
            } else {
                form.reset();
                form.clearErrors();
            }
        }
    }
);
const emit = defineEmits(["update:show"]);
const close = () => {
    emit("update:show", false);
    form.clearErrors();
    form.reset();
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

                    <div v-if="form.errors.unauthorized_action"
                        class="alert alert-danger d-flex justify-content-between align-items-center" role="alert">
                        <div class="d-flex align-items-center gap-2 fw-semibold">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ form.errors.unauthorized_action }}
                        </div>
                        <button @click="form.clearErrors('unauthorized_action')" type="button"
                            class="bg-transparent rounded-circle border-0 fs-4">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <form-wrapper @submit.prevent="submit">

                        <div class="mb-3">
                            <input-label labelClass="mb-1" for="name_input" value="Nama Lengkap" />
                            <input-text :error="form.errors.name" v-model="form.name" name="name"
                                placeholder="Cth: Dimas Anggara" />
                            <input-error :message="form.errors.name" />
                        </div>

                        <div class="mb-3">
                            <input-label labelClass="mb-1" for="email_input" value="Email" />
                            <input-text :disabled="isEdit" name="email" :error="form.errors.email" v-model="form.email"
                                type="email" placeholder="dimas@perusahaan.com" />
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

                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                        <button type="button" class="btn btn btn-light border-0 fw-medium px-4"
                            @click.prevent="close">Batal</button>
                        <button @click.prevent="submit" type="button" :disabled="form.processing"
                            class="btn btn-height-1 fw-semibold" :class="isEdit ? 'btn-success' : 'btn-primary'">
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
