<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import axios from "axios";
const props = defineProps({
    idProduct: {
        type: String,
        default: null
    },
    category: Array,
    show: Boolean,
});

const categoryOptions = computed(() => [
    ...props.category.map(ctg => ({
        label: ctg.category,
        value: ctg.category,
    }))
]);
console.log(categoryOptions.value);
const isEdit = computed(() => !!props.idProduct);
const productImage = ref(null);
const product = ref(null);
const form = useForm({
    name: '',
    category: null,
    item_condition: 'new',
    source: 'manual',
    link: '',
    image: null,
    base_price: 0,
    discount_percentage: 0,
    discount_price: 0,
    status: 'published',
    valid_from: '',
    valid_until: '',
});
const loadProduct = async () => {

    if (!props.idProduct) {
        resetForm();
        return
    }

    try {
        const { data } = await axios.get(`/product/show/${props.idProduct}`);
        product.value = data;
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    }

}
const fillForm = (data) => {
    console.log(data);

    form.defaults({
        name: data.name,
        category: data.category || null,
        item_condition: data.item_condition,
        source: data.source,
        link: data.link,
        image: null,
        base_price: data.prices[0]?.base_price || 0,
        discount_percentage: data.prices[0]?.discount_percentage || 0,
        discount_price: data.prices[0]?.discount_price || 0,
        status: data.status || 'published',
        valid_from: data.valid_from,
        valid_until: data.valid_until,
    });
    form.reset();
}
const resetForm = () => {
    form.defaults({
        name: '',
        category: null,
        item_condition: 'new',
        source: 'manual',
        link: '',
        image: null,
        base_price: 0,
        discount_percentage: 0,
        discount_price: 0,
        status: 'published',
        valid_from: '',
        valid_until: '',
    })
    form.reset();
    form.clearErrors();
}
const submit = () => {
    const url = isEdit.value
        ? route('product.update', props.idProduct)
        : route('product.store');


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
        if (props.idProduct) {
            await loadProduct();
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
const imagePreview = ref(null);
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
            ]" :show="props.show" :title="isEdit ? 'Edit Data Produk' : 'Buat Produk Baru'" :mobileFullscreen="false"
                @closed="close">

                <template #body>
                    <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

                    <form-wrapper @submit.prevent="submit">
                        <div class="row g-4">

                            <div class="col-xl-7">
                                <h6 class="fw-bold text-muted small text-uppercase mb-3">Informasi Dasar</h6>
                                <div class="mb-3">
                                    <label for="product_name_input" class="form-label fw-medium small">Nama Produk <span
                                            class="text-danger">*</span></label>
                                    <input-text v-model="form.name" name="product_name"
                                        placeholder="Cth: Sepatu Kasual" />
                                    <input-error :message="form.errors.name" />
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="category_input" class="form-label fw-medium small">Kategori <span
                                                class="text-danger">*</span></label>
                                        <select-input text="Pilih Kategori Produk" name="category"
                                            v-model="form.category" :options="categoryOptions" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium small">Kondisi Barang</label>
                                        <select v-model="form.item_condition" class="form-select">
                                            <option value="new">Baru (New)</option>
                                            <option value="used">Bekas (Used)</option>
                                            <option value="refurbished">Refurbished</option>
                                            <option value="damaged">Rusak (Damaged)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-8">
                                        <label class="form-label fw-medium small">Link/URL Referensi</label>
                                        <input type="url" v-model="form.link" class="form-control"
                                            :disabled="form.source === 'manual'" placeholder="Wajib jika Scrape">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5 border-start">
                                <h6 class="fw-bold text-muted small text-uppercase mb-3">Media Foto</h6>
                                <div class="mb-4">
                                    <div v-if="isEdit && (product?.image_path || product?.image_link)"
                                        class="mb-2 text-center bg-light p-2 rounded border">
                                        <img :src="product.image_link || `/storage/${product.image_path}`"
                                            class="img-fluid rounded" style="max-height: 120px; object-fit: cover;">
                                    </div>

                                    <label class="form-label fw-medium small">Upload File</label>
                                    <input type="file" @input="form.image = $event.target.files[0]"
                                        class="form-control form-control-sm" accept="image/*">
                                    <small class="text-muted" style="font-size: 11px;">Maksimal 2MB. Biarkan kosong jika
                                        tidak diubah.</small>
                                </div>

                                <h6 class="fw-bold text-muted small text-uppercase mt-4 mb-3 pt-3 border-top">Masa
                                    Berlaku Harga</h6>
                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <label class="form-label fw-medium small mb-1">Mulai</label>
                                        <input type="date" v-model="form.valid_from"
                                            class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-medium small mb-1">Sampai (Opsional)</label>
                                        <input type="date" v-model="form.valid_until"
                                            class="form-control form-control-sm">
                                    </div>
                                </div>

                                <h6 class="fw-bold text-muted small text-uppercase mt-4 mb-3 pt-3 border-top">Publikasi
                                </h6>
                                <div>
                                    <select v-model="form.status" class="form-select form-select-lg fs-6">
                                        <option value="published">✅ Aktif (Published)</option>
                                        <option value="draft">📁 Draf (Draft)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form-wrapper>

                </template>

                <template #footer>
                    <!-- <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">
                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4"
                            @click.prevent="close">Batal</button>
                        <button @click.prevent="submit" type="button" :disabled="form.processing"
                            class="btn btn-height-1 fw-semibold" :class="isEdit ? 'btn-c-success' : 'btn-c-primary'">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else :class="isEdit ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"></i>
                            {{ submitTextButton }}
                        </button>
                    </div> -->
                </template>

            </modal>
        </div>
    </div>
</template>
