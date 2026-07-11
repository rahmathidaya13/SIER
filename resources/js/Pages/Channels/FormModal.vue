<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import axios from "axios";
const props = defineProps({
    idChannel: {
        type: String,
        default: null
    },
    show: Boolean,
});


const isEdit = computed(() => !!props.idChannel);
const form = useForm({
    name: '',
    code: '',
    icon: '',
});
const loadChannel = async () => {

    if (!props.idChannel) {
        resetForm();
        return
    }

    try {
        const { data } = await axios.get(`/channel/show/${props.idChannel}`);
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    }

}
const fillForm = (data) => {
    console.log(data);
    form.defaults({
        name: data.name || '',
        code: data.code || '',
        icon: data.icon || '',
    });
    form.reset();
}
const resetForm = () => {
    form.defaults({
        name: '',
        code: '',
        icon: '',
    })
    form.reset();
    form.clearErrors();
}
const submit = () => {
    const url = isEdit.value
        ? route('channel.update', props.idChannel)
        : route('channel.store');


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
        if (props.idChannel) {
            await loadChannel();
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


const presetIcons = [
    'bi-whatsapp text-success',
    'bi-instagram text-danger',
    'bi-tiktok text-dark',
    'bi-facebook text-primary',
    'bi-twitter-x text-dark',
    'bi-youtube text-danger',
    'bi-telegram text-primary',
    'bi-shop text-success',
    'bi-bag-fill text-warning',
    'bi-globe text-info',
    'bi-megaphone text-warning',
    'bi-envelope text-danger',
    'bi-telephone text-success',
    'bi-people-fill text-info',
];
const selectIcon = (iconClass) => {
    form.icon = iconClass;
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
            ]" :show="props.show" :title="isEdit ? 'Edit Channel Prospek' : 'Tambah Channel Baru'"
                :mobileFullscreen="false" @closed="close">

                <template #body>


                    <form-wrapper @submit.prevent="submit">
                        <div class="mb-3">
                            <label for="name_icon_input" class="form-label fw-semibold text-muted small">Nama
                                Channel</label>
                            <input-text v-model="form.name" name="name_icon" :error="form.errors.name"
                                placeholder="Contoh: WhatsApp" inputClass="text-capitalize" />
                            <input-error :message="form.errors.name" />
                        </div>

                        <div class="mb-3">
                            <label for="code_input" class="form-label fw-semibold text-muted small">Kode (Unik)</label>
                            <input-text name="code" v-model="form.code" :error="form.errors.code"
                                inputClass="text-uppercase" placeholder="Contoh: [WA, IG, FB Dll]" />
                            <input-error :message="form.errors.code" />
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small">Pilih Ikon Platform</label>
                            <div class="d-flex flex-wrap gap-2 bg-c-light border align-items-center rounded-3 p-3">
                                <button v-for="icons in presetIcons" :key="icons" type="button"
                                    @click="selectIcon(icons)"
                                    class="btn btn-light border shadow-sm fs-3 d-flex align-items-center justify-content-center transition-all"
                                    style="width: 60px; height: 60px;" :class="[
                                        { 'border-primary border-1 text-primary scale-up': form.icon === icons },
                                        { 'bg-white': form.icon !== icons }
                                    ]">
                                    <i :class="icons"></i>
                                </button>
                            </div>
                            <input-error :message="form.errors.icon" />
                        </div>
                    </form-wrapper>

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
