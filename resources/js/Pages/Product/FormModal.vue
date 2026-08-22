<script setup>
import { computed, ref, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import axios from "axios";
import { formatNumber } from "chart.js/helpers";
const props = defineProps({
    idProduct: {
        type: String,
        default: null
    },
    show: Boolean,
});

const isEdit = computed(() => !!props.idProduct);
const imagePreview = ref(null);
const isDiscountActive = ref(false);
const isLoading = ref(false);
const product = ref(null);

const form = useForm({
    name: '',
    category: null,
    item_condition: null,
    type_item: null,
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
    isLoading.value = true
    try {
        const { data } = await axios.get(`/product/show/${props.idProduct}`);
        product.value = data;
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    } finally {
        isLoading.value = false
    }

}
const fillForm = (data) => {
    form.defaults({
        name: data.name,
        category: data.category || null,
        item_condition: data.item_condition,
        type_item: data.type_item,
        source: data.source,
        link: data.link,
        image: null,
        base_price: data.prices[0]?.base_price || 0,
        discount_percentage: data.prices[0]?.discount_percentage || 0,
        discount_price: data.prices[0]?.discount_price || 0,
        status: data.prices[0]?.status || 'published',
        valid_from: data.prices[0]?.valid_from,
        valid_until: data.prices[0]?.valid_until,
    });

    isDiscountActive.value = data.prices[0]?.discount_percentage > 0
    imagePreview.value = getImageUrl(data);
    form.reset();
}
const resetForm = () => {
    form.defaults({
        name: '',
        category: null,
        item_condition: null,
        type_item: null,
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
    isDiscountActive.value = false
    imagePreview.value = null;
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

watch([() => form.base_price, () => form.discount_percentage], ([newPrice, newPercent]) => {
    if (newPrice > 0 && newPercent > 0) {
        const discountAmount = (newPrice * newPercent) / 100;
        form.discount_price = Math.round(newPrice - discountAmount);
    } else {
        form.discount_price = 0
    }
});

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
    isDiscountActive.value = false
}
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    return isEdit.value ? 'Simpan Perubahan' : ' Simpan';
});

const handleDiscount = () => {
    isDiscountActive.value = !isDiscountActive.value
    if (!isDiscountActive.value) {
        form.discount_price = 0
        form.discount_percentage = 0
    } else {
        form.discount_price = isEdit.value ? product.value.prices[0].discount_price : 0
    }
}
const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
}
const getImageUrl = (item) => {
    if (item.source === 'scrape' && item.image_link) {
        return item.image_link; // Gambar dari URL luar
    }
    if (item.image_path) {
        return `/storage/${item.image_path}`; // Gambar lokal dari storage Laravel
    }
    return '/storage/no_image/no-image.png'; // Fallback jika tidak ada gambar
};

</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="900px" size="modal-lg" :icon="[
                'text-coffee-main',
                {
                    'bi bi-pencil-square text-primary': isEdit,
                    'bi bi-plus-circle-fill text-primary': !isEdit
                }
            ]" :show="props.show" :title="isEdit ? 'Edit Data Produk' : 'Buat Produk Baru'" :mobileFullscreen="true"
                @closed="close">

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
                            <div class="row g-3">
                                <div class="col-lg-4 col-md-4 col-xl-4">
                                    <h6 class="fw-bold text-dark mb-3">Foto Produk</h6>

                                    <div class="image-upload-box position-relative rounded-4 border border-2 border-dashed bg-light text-center transition-all"
                                        :class="{ 'border-primary': imagePreview, 'border-secondary': !imagePreview }">

                                        <input type="file" @change="handleImageUpload"
                                            class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                                            style="cursor: pointer;" accept="image/*">

                                        <div v-if="imagePreview" class="w-100 h-100 position-relative p-2">
                                            <img :src="imagePreview" class="img-product img-fluid">
                                            <div class="position-absolute bottom-0 start-50 translate-middle-x mb-3">
                                                <span @click="imagePreview = null"
                                                    class="badge bg-dark bg-opacity-75 px-3 py-2 rounded-pill shadow-sm cursor-pointer">Klik
                                                    untuk ganti foto</span>
                                            </div>
                                        </div>

                                        <div v-else class="d-flex flex-column align-items-center justify-content-center"
                                            style="min-height: 300px;">
                                            <div class="bg-transparent p-3 mb-3 text-primary">
                                                <i class="bi bi-camera fs-1"></i>
                                            </div>
                                            <span class="fw-bold text-dark">Upload Gambar</span>
                                            <small class="text-muted mt-1">Klik atau drag & drop file<br>Maksimal ukuran
                                                2MB.</small>
                                        </div>
                                    </div>
                                    <div v-if="form.progress"
                                        class="mt-4 p-3 bg-light rounded-4 border border-primary border-opacity-25 shadow-sm">
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-2 small fw-bold text-primary">
                                            <span><i class="bi bi-cloud-arrow-up me-1"></i> Menyimpan produk...</span>

                                            <span>{{ form.progress.percentage }}%</span>
                                        </div>
                                        <div class="progress rounded-pill shadow-sm"
                                            style="height: 8px; background-color: #e9ecef;">

                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                                role="progressbar" :style="{ width: form.progress.percentage + '%' }">
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2 text-center" style="font-size: 10px;">
                                            Mohon jangan tutup jendela ini
                                        </small>
                                    </div>
                                    <div v-if="form.errors.image" class="text-danger small mt-2 fw-medium text-center">
                                        {{
                                            form.errors.image }}</div>
                                </div>

                                <div class="col-xl-8 col-md-8 col-xl-8">
                                    <h6 class="fw-bold text-muted small text-uppercase mb-3">Informasi Dasar</h6>
                                    <div class="row g-3 mb-3">
                                        <div class="col-12">
                                            <input-label for="product_name_input"
                                                labelClass="fw-medium text-secondary small mb-2" value="Nama Produk" />
                                            <input-text v-model="form.name" name="product_name"
                                                :error="form.errors.name" placeholder="Cth:Mesin cetak mie mks-200" />
                                            <input-error :message="form.errors.name" />
                                        </div>
                                        <div class="col-md-6">
                                            <input-label for="category_input"
                                                labelClass="fw-medium text-secondary small mb-2" value="Kategori" />
                                            <input-text v-model="form.category" name="category"
                                                :error="form.errors.category" placeholder="Cth: Mesin makanan" />
                                            <input-error :message="form.errors.category" />
                                        </div>
                                        <div class="col-md-6">
                                            <input-label for="condition_input"
                                                labelClass="fw-medium text-secondary small mb-2"
                                                value="Kondisi Barang" />
                                            <select-input text="Pilih kondisi produk" v-model="form.item_condition"
                                                name="condition" :error="form.errors.item_condition" :options="[
                                                    { value: 'new', label: 'Baru (New)' },
                                                    { value: 'used', label: 'Bekas (Used)' },
                                                    { value: 'refurbished', label: 'Refurbished' },
                                                    { value: 'damaged', label: 'Rusak (Damaged)' },
                                                    { value: 'discontinued', label: 'Discontinued' },
                                                ]" />
                                            <input-error :message="form.errors.item_condition" />
                                        </div>
                                        <div class="col-12">
                                            <input-label for="type_item_input"
                                                labelClass="fw-medium text-secondary small mb-2" value="Tipe Produk" />
                                            <select-input text="Pilih tipe produk" v-model="form.type_item"
                                                name="type_item" :error="form.errors.type_item" :options="[
                                                    { value: 'product', label: 'Produk' },
                                                    { value: 'sparepart', label: 'Sparepart' },
                                                    { value: 'accessories', label: 'Aksesoris' },
                                                    { value: 'other', label: 'Lainnya' },
                                                ]" />
                                            <input-error :message="form.errors.type_item" />
                                        </div>
                                        <div class="col-12">
                                            <input-label for="link_input"
                                                labelClass="fw-medium text-secondary small mb-2"
                                                value="Link/URL Referensi (Opsional)" />
                                            <input-text v-model="form.link" name="link" :error="form.errors.link"
                                                placeholder="https://" />
                                            <input-error :message="form.errors.link" />
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-muted small text-uppercase mt-2">Penetapan Harga</h6>
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6 col-12">
                                            <input-label for="base_price_input"
                                                labelClass="fw-medium text-secondary small mb-2" value="Harga Dasar" />
                                            <input-currency v-model="form.base_price" name="base_price" :decimals="0"
                                                :error="form.errors.base_price" />
                                            <input-error :message="form.errors.base_price" />
                                        </div>

                                        <div class="col-md-6 col-12">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <input-label for="discount_percentage_input"
                                                    labelClass="fw-bold text-danger small"
                                                    value="Persentase Diskon (%)" />
                                                <button @click.prevent="handleDiscount" type="button"
                                                    class="btn btn-sm btn-link text-decoration-none p-0">{{
                                                        !isDiscountActive
                                                            ? 'Aktifkan' : 'Batal' }}</button>
                                            </div>
                                            <input-number :isValid="isDiscountActive" :disabled="!isDiscountActive"
                                                placeholdeer="0" :maxChar="3" v-model="form.discount_percentage"
                                                inputClass="text-danger" name="discount_percentage"
                                                :error="form.errors.discount_percentage" />

                                            <input-error :message="form.errors.discount_percentage" />
                                        </div>
                                        <div class="col-12" v-if="isDiscountActive">
                                            <input-label labelClass="fw-bold text-success small mb-2"
                                                value="Harga Akhir" />
                                            <input-currency :readonly="isDiscountActive" :decimals="0"
                                                v-model="form.discount_price" inputClass="text-success"
                                                name="discount_price" :error="form.errors.discount_price" />
                                            <small class="text-muted mt-1 d-block" style="font-size: 11px;">*Dihitung
                                                otomatis oleh sistem</small>
                                            <input-error :message="form.errors.discount_price" />
                                        </div>
                                    </div>

                                    <div class="row g-3 bg-light rounded-4 border mt-3 pb-4 px-2">
                                        <div class="col-md-6 col-12">
                                            <input-label for="status_select"
                                                labelClass="fw-medium text-secondary small mb-2" value="Status
                                            Publikasi" />
                                            <select-input v-model="form.status" name="status"
                                                :error="form.errors.status"
                                                :selectClass="form.status === 'published' ? 'text-success border-success' : 'text-secondary'"
                                                :options="[{ value: 'published', label: '✅ Published' }, { value: 'draft', label: '📁 Draft' }]" />
                                            <input-error :message="form.errors.status" />
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <input-label for="valid_from_input"
                                                labelClass="fw-medium text-secondary small mb-2"
                                                value="Berlaku Mulai" />
                                            <input-text type="date" v-model="form.valid_from" name="valid_from"
                                                :error="form.errors.valid_from" />
                                            <input-error :message="form.errors.valid_from" />
                                        </div>
                                        <div class="col-12">
                                            <input-label for="valid_until_input"
                                                labelClass="fw-medium text-secondary small mb-2"
                                                value="Berlaku Sampai(Optional)" />
                                            <input-text type="date" v-model="form.valid_until" name="valid_until"
                                                :error="form.errors.valid_until" />
                                        </div>
                                    </div>
                                </div>
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
.border-dashed {
    border-style: dashed !important;
}

.image-upload-box:hover {
    background-color: #f8f9fa;
    border-color: #0d6efd !important;
}

.transition-all {
    transition: all 0.3s ease;
}

.img-product {
    min-height: 300px;
    height: 300px;
    object-fit: fill;
    object-position: center;
}
</style>
