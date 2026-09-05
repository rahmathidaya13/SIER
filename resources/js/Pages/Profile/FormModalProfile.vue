<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import axios from "axios";
const props = defineProps({
    show: Boolean,
});
const page = usePage();
const users = computed(() => page.props.auth.user);
const isLoading = ref(false);
const photoPreview = ref(null);
const fileInput = ref(null);
const profilePreview = computed(() => !!users?.value.id);
const isEdit = ref(false);
const flashMessage = ref(null);
const emit = defineEmits(["update:show"]);
const close = () => {
    emit("update:show", false);
    resetForm();
    isEdit.value = false
}
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return profilePreview.value ? 'Simpan Perubahan' : ' Simpan';
});

const form = useForm({
    name: '',
    email: '',
    numberPhone: '',
    gender: null,
    city: '',
    province: '',
    address: '',
    photo: null,
});
const submit = () => {
    const url = route('profile.update', users?.value.id);
    const transformData = (data) => ({ ...data, _method: 'put' })
    form.transform(transformData).post(url, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: async () => {
            // close();
            await loadUser();
            isEdit.value = false
            flashMessage.value = 'Perubahan Data Profile Berhasil disimpan';
            setTimeout(() => {
                flashMessage.value = null;
            }, 5000);
        },

    });
};

const loadUser = async () => {
    isLoading.value = true
    try {
        const { data } = await axios.get(`/profile/detail/${users?.value.id}`);
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    } finally {
        isLoading.value = false
    }

}
const fillForm = (data) => {
    console.log(data);
    form.defaults({
        name: data.name || '-',
        email: data.email || '-',
        numberPhone: data.profile.phone || '-',
        gender: data.profile.gender || null,
        city: data.profile.city || '-',
        province: data.profile.province || '-',
        address: data.profile.address || '-',
    });
    photoPreview.value = getImageUrl(data.profile);
    form.reset();
}
const resetForm = () => {
    form.defaults({
        name: '',
        email: '',
        numberPhone: '',
        city: '',
        gender: '',
        province: '',
        address: '',
    })
    photoPreview.value = null;
    form.reset();
    form.clearErrors();
}
const getImageUrl = (item) => {
    if (item.photo !== null && item.photo !== undefined) {
        return `/storage/${item.photo}`; // load gambar dari storage jika ada
    }

    return '/storage/no_image/user-icon.png'; // Fallback jika tidak ada gambar
};

watch(
    () => props.show,
    async (isOpen) => {
        if (!isOpen) return;
        if (users?.value.id) {
            await loadUser();
        } else {
            resetForm();
        }
    }, {
    immediate: true
}
);

const selectNewPhoto = () => {
    fileInput.value.click();
};

const updatePhotoPreview = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.photo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const getRoleName = (roleName) => {
    const roles = {
        'developer': {
            label: 'Developer',
            color: 'bg-dark text-white'
        },
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
            color: 'bg-info text-white'
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
const borderCheck = (inputValue) => {
    if (form.errors[inputValue]) return 'border-danger';
    return form[inputValue] ? 'border-success' : 'border-secondary';
}
const genderCheck = (value) => {
    const gender = {
        'L': 'Laki-laki',
        'P': 'Perempuan'
    }
    return gender[value]
}
</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="750px" size="modal-lg" icon="bi bi-person-bounding-box text-primary" :show="props.show"
                title="Pengaturan Profile" :mobileFullscreen="true" @closed="close" :footer="isEdit">

                <template #body>
                    <div v-if="isLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-3 small fw-medium">Sedang mengambil data...</p>
                    </div>

                    <div v-else>

                        <transition name="slide-fade">
                            <template v-if="flashMessage">
                                <div class="alert bg-c-success p-2" role="alert">
                                    <span><i class="bi bi-check-circle fw-bold"></i>
                                        {{ flashMessage }}
                                    </span>
                                </div>
                            </template>
                        </transition>

                        <div class="d-flex flex-column align-items-center mb-4 pb-4 border-bottom border-light">
                            <div class="position-relative mb-3">

                                <div class="rounded-circle overflow-hidden border border-3 border shadow-sm bg-light d-flex align-items-center justify-content-center"
                                    style="width: 180px; height: 180px;">

                                    <img v-if="photoPreview" :src="photoPreview" class="w-100 h-100 object-fit-cover"
                                        alt="Preview">
                                    <img v-else-if="users?.profile.photo" :src="users?.profile.photo"
                                        class="w-100 h-100 object-fit-cover" alt="Profile">
                                    <i v-else class="bi bi-person text-secondary" style="font-size: 3rem;"></i>
                                </div>

                                <button v-if="isEdit" type="button" @click="selectNewPhoto"
                                    class="btn btn-primary btn-sm me-3 rounded-circle position-absolute bottom-0 end-0 shadow border border-2 border-white d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px; " title="Ganti Foto">
                                    <i class="bi bi-camera-fill" style="font-size: 18px;"></i>
                                </button>

                                <input type="file" ref="fileInput" class="d-none" accept="image/*"
                                    @change="updatePhotoPreview">
                            </div>

                            <h5 class="fw-bold text-dark mb-2 text-capitalize ">{{ users?.name || 'User' }}</h5>

                            <div class="d-flex gap-1">
                                <span
                                    :class="`badge text-uppercase ${getRoleName(users?.roles?.[0]?.name).color} mt-1 px-3`">
                                    <i class="bi bi-shield-check me-1"></i>
                                    {{
                                        getRoleName(users?.roles?.[0]?.name).label ||
                                        'Karyawan' }}
                                </span>
                                <span class="badge badge-soft-info mt-1 px-3">
                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                    {{ users?.branch?.name }}
                                </span>
                            </div>
                            <input-error :message="form.errors.photo" class="mt-2 text-center" />
                        </div>

                        <div class="row g-3 p-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="text-primary fw-bold small mb-2"><i
                                            class="bi bi-person-lines-fill me-1"></i>
                                        INFORMASI DASAR</h6>
                                    <div style="font-size: 13px;" class="text-muted cursor-pointer "
                                        @click="isEdit = !isEdit" title="Edit Profile">
                                        <i
                                            :class="` me-1 fas ${isEdit ? 'fa-times-circle text-danger' : 'fa-edit text-primary'}`"></i>
                                        <span :class="isEdit ? 'text-danger' : 'text-primary'">{{ isEdit ? 'Batal' :
                                            'Ubah' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex flex-column">
                                <input-label for="name_input" value="Nama Lengkap" labelClass="mb-2 text-muted"
                                    style="font-size: 12px;" />
                                <template v-if="isEdit">
                                    <div :class="`border-bottom ${borderCheck('name')}`">
                                        <input-text :error="form.errors.name" v-model="form.name" name="name"
                                            inputClass="border-0 shadow-none p-0 mb-1 rounded-0 fw-bold"
                                            placeholder="Nama lengkap" />
                                    </div>
                                    <input-error :message="form.errors.name" />
                                </template>
                                <span v-if="!isEdit" class="fw-bold">{{ users?.name }}</span>
                            </div>
                            <div class="col-md-6 d-flex flex-column">
                                <input-label for="email_input" value="Alamat Email" labelClass="mb-2 text-muted"
                                    style="font-size: 12px;" />
                                <template v-if="isEdit">
                                    <div :class="`border-bottom ${borderCheck('email')}`">
                                        <input-text
                                            inputClass="border-0 shadow-none p-0 mb-1 rounded-0 bg-transparent fw-bold"
                                            :error="form.errors.email" :disabled="true" type="email"
                                            v-model="form.email" name="email" placeholder="Email aktif" />
                                    </div>
                                    <input-error :message="form.errors.email" />
                                </template>
                                <span v-if="!isEdit" class="fw-bold text-primary">{{ users?.email }}</span>
                            </div>
                            <div class="col-md-6 d-flex flex-column">
                                <input-label for="phone_input" value="No.Handphone" labelClass="mb-2 text-muted"
                                    style="font-size: 12px;" />
                                <template v-if="isEdit">
                                    <div :class="`border-bottom ${borderCheck('numberPhone')}`">
                                        <input-text inputClass="border-0 shadow-none p-0 mb-1 rounded-0 fw-bold"
                                            :error="form.errors.numberPhone" v-model="form.numberPhone" name="phone"
                                            placeholder="Nomor handphone aktif" />
                                    </div>
                                    <input-error :message="form.errors.numberPhone" />
                                </template>
                                <span v-if="!isEdit" class="fw-bold">{{ users?.profile?.phone }}</span>
                            </div>
                            <div class="col-md-6 d-flex flex-column">
                                <input-label for="gender_select" value="Jenis Kelamin" labelClass="mb-2 text-muted"
                                    style="font-size: 12px;" />

                                <template v-if="isEdit">
                                    <div :class="`border-bottom ${borderCheck('gender')}`">
                                        <select-input selectClass="border-0 shadow-none p-0 mb-1 rounded-0 fw-bold"
                                            text="-- Pilih Jenis Kelamin --" name="gender" :error="form.errors.gender"
                                            v-model="form.gender" :options="[
                                                { value: 'L', label: 'Laki-laki' },
                                                { value: 'P', label: 'Perempuan' }
                                            ]" />
                                    </div>
                                    <input-error :message="form.errors.gender" />
                                </template>
                                <span v-if="!isEdit" class="fw-bold">
                                    {{ genderCheck(users?.profile?.gender) }}
                                </span>
                            </div>

                            <div class="col-md-6 d-flex flex-column">
                                <input-label for="city_input" value="Kota/Kabupaten" labelClass="mb-2 text-muted"
                                    style="font-size: 12px;" />

                                <template v-if="isEdit">
                                    <div :class="`border-bottom ${borderCheck('city')}`">
                                        <input-text inputClass="border-0 shadow-none p-0 mb-1 rounded-0 fw-bold"
                                            :error="form.errors.city" v-model="form.city" name="city"
                                            placeholder="Kota tempat tinggal" />
                                    </div>
                                    <input-error :message="form.errors.city" />
                                </template>
                                <span v-if="!isEdit" class="fw-bold">{{ users?.profile?.city }}</span>
                            </div>

                            <div class="col-md-6 d-flex flex-column ">
                                <input-label for="province_input" value="Provinsi" labelClass="mb-2 text-muted"
                                    style="font-size: 12px;" />
                                <template v-if="isEdit">
                                    <div :class="`border-bottom ${borderCheck('province')}`">
                                        <input-text inputClass="border-0 shadow-none p-0 mb-1 rounded-0 fw-bold"
                                            :error="form.errors.province" v-model="form.province" name="province"
                                            placeholder="Provinsi tempat tinggal" />
                                    </div>
                                    <input-error :message="form.errors.province" />
                                </template>
                                <span v-if="!isEdit" class="fw-bold">{{ users?.profile?.province }}</span>

                            </div>
                            <div class="col-md-12 d-flex flex-column ">
                                <input-label for="address_input" value="Alamat" labelClass="mb-2 text-muted"
                                    style="font-size: 12px;" />

                                <template v-if="isEdit">
                                    <div :class="`border-bottom ${borderCheck('address')}`">
                                        <textarea v-model="form.address" :class="`form-control border-0 shadow-none p-0 mb-1 rounded-0 fw-bold resize-none
                                        ${form.address ? 'is-valid' : ''}`" name="address" id="address_input" cols="3"
                                            rows="3" placeholder="Masukkan alamat yang sesuai..."></textarea>
                                    </div>
                                    <input-error :message="form.errors.address" />
                                </template>
                                <span v-if="!isEdit" class="fw-bold">{{ users?.profile?.address }}</span>

                            </div>
                        </div>
                    </div>

                </template>

                <template #footer>
                    <div v-if="!isLoading && isEdit" class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4"
                            @click.prevent="close">Batal</button>
                        <button @click.prevent="submit" type="button" :disabled="form.processing"
                            class="btn btn-height-1 fw-semibold"
                            :class="profilePreview ? 'btn-c-success' : 'btn-c-primary'">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else :class="profilePreview ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"></i>
                            {{ submitTextButton }}
                        </button>
                    </div>
                </template>

            </modal>
        </div>
    </div>
</template>
<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: .25s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
