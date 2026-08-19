<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import axios from "axios";
const props = defineProps({
    show: Boolean,
});

const isLoading = ref(false);
const user = usePage().props.auth.user;
const photoPreview = ref(null);
const fileInput = ref(null);
const profilePreview = computed(() => !!user?.id);
const emit = defineEmits(["update:show"]);
const close = () => {
    emit("update:show", false);
}
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return profilePreview.value ? 'Simpan Perubahan' : ' Simpan';
});

const form = useForm({
    name: '',
    email: '',
    photo: null,
    numberPhone: '',
    gender: '',
    city: '',
    province: '',
    address: '',
});
const submit = () => {
    const url = route('profile.update', user.id);
    const transformData = (data) => ({ ...data, _method: 'put' })
    form.transform(transformData).post(url, {
        preserveScroll: false,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            close();
            resetForm();
        },

    });
};

const loadUser = async () => {
    isLoading.value = true
    try {
        const { data } = await axios.get(`/profile/detail/${user.id}`);
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
        name: data.name,
        email: data.email,
        numberPhone: data.phone,
        gender: data.gender,
        city: data.city,
        province: data.province,
        address: data.address,
    });

    // isDiscountActive.value = data.prices[0]?.discount_percentage > 0
    // imagePreview.value = getImageUrl(data);
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
    form.reset();
    form.clearErrors();
}
watch(
    () => props.show,
    async (isOpen) => {
        if (!isOpen) return;
        if (user) {
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
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="750px" size="modal-lg" icon="bi bi-person-bounding-box text-primary" :show="props.show"
                title="Pengaturan Profile" :mobileFullscreen="true" @closed="close">

                <template #body>
                    <div v-if="isLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-3 small fw-medium">Sedang mengambil data...</p>
                    </div>

                    <div v-else>
                        <div class="d-flex flex-column align-items-center mb-4 pb-4 border-bottom border-light">
                            <div class="position-relative mb-3">

                                <div class="rounded-circle overflow-hidden border border-3 border shadow-sm bg-light d-flex align-items-center justify-content-center"
                                    style="width: 150px; height: 150px;">

                                    <img v-if="photoPreview" :src="photoPreview" class="w-100 h-100 object-fit-cover"
                                        alt="Preview">
                                    <img v-else-if="user?.profile.photo" :src="user?.profile.photo"
                                        class="w-100 h-100 object-fit-cover" alt="Profile">
                                    <i v-else class="bi bi-person text-secondary" style="font-size: 3rem;"></i>
                                </div>

                                <button type="button" @click="selectNewPhoto"
                                    class="btn btn-primary btn-sm rounded-circle position-absolute bottom-0 end-0 shadow border border-2 border-white d-flex align-items-center justify-content-center"
                                    style="width: 32px; height: 32px;" title="Ganti Foto">
                                    <i class="bi bi-camera-fill" style="font-size: 12px;"></i>
                                </button>

                                <input type="file" ref="fileInput" class="d-none" accept="image/*"
                                    @change="updatePhotoPreview">
                            </div>

                            <h5 class="fw-bold text-dark mb-0 text-capitalize">{{ user?.name }}</h5>
                            <span :class="`badge ${getRoleName(user?.roles?.[0]?.name).color} mt-1 px-3`">{{
                                getRoleName(user?.roles?.[0]?.name).label ||
                                'Karyawan' }}</span>
                            <input-error :message="form.errors.photo" class="mt-2 text-center" />
                        </div>

                        <div class="row px-2 g-3">
                            <div class="col-12">
                                <h6 class="text-primary fw-bold small mb-2"><i class="bi bi-person-lines-fill me-1"></i>
                                    INFORMASI DASAR</h6>
                            </div>
                            <div class="col-md-6">
                                <input-label for="name_input" value="Nama Lengkap" labelClass="mb-2" />
                                <input-text :error="form.errors.name" v-model="form.name" name="name"
                                    placeholder="Nama lengkap" />
                                <input-error :message="form.errors.name" />
                            </div>
                            <div class="col-md-6">
                                <input-label for="email_input" value="Alamat Email" labelClass="mb-2" />
                                <input-text :error="form.errors.email" :disabled="true" type="email"
                                    v-model="form.email" name="email" placeholder="Email aktif" />
                                <input-error :message="form.errors.email" />
                            </div>
                            <div class="col-md-6">
                                <input-label for="phone_input" value="No.Handphone" labelClass="mb-2" />
                                <input-text :error="form.errors.numberPhone" v-model="form.numberPhone" name="phone"
                                    placeholder="Nomor handphone aktif" />
                                <input-error :message="form.errors.numberPhone" />
                            </div>
                            <div class="col-md-6">
                                <input-label for="gender_select" value="Jenis Kelamin" labelClass="mb-2" />
                                <select-input text="-- Pilih Jenis Kelamin --" name="gender" :error="form.errors.gender" v-model="form.gender" :options="[
                                    { value: 'L', label: 'Laki-laki' },
                                    { value: 'P', label: 'Perempuan`' }
                                ]" />
                                <input-error :message="form.errors.gender" />
                            </div>

                            <div class="col-md-12">
                                <input-label for="city_input" value="Kota/Kabupaten" labelClass="mb-2" />
                                <input-text :error="form.errors.city" v-model="form.city" name="city"
                                    placeholder="Kota tempat tinggal" />
                                <input-error :message="form.errors.city" />
                            </div>
                            <div class="col-md-12">
                                <input-label for="province_input" value="Provinsi" labelClass="mb-2" />
                                <input-text :error="form.errors.province" v-model="form.province" name="province"
                                    placeholder="Provinsi tempat tinggal" />
                                <input-error :message="form.errors.province" />
                            </div>
                            <div class="col-md-12">
                                <input-label for="address_input" value="Alamat" labelClass="mb-2" />
                                <text-area :error="form.errors.address" name="address" v-model="form.address" :rows="3"
                                    placeholder="Masukkan alamat yang sesuai..." />
                                <input-error :message="form.errors.address" />
                            </div>
                        </div>
                    </div>

                </template>

                <template #footer>
                    <div v-if="!isLoading" class="d-flex justify-content-end gap-2 p-2 bg-transparent">
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
