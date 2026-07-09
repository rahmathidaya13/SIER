<script setup>
import { computed, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import { useRowAlert } from '@/Composables/useAlert';
import { currencyFormat } from "@/Helpers/currencyFormat";
import { formatDate } from "@/Helpers/formatDate";
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

    // Field Konversi Closing
    deal_price: 0,
    paid_amount: 0,
    payment_status: 'LUNAS',
    payment_method: 'cash',
    quantity: '',
    bank_name: '',
    notes_sales: ''
});

// Auto-Kalkulasi Uang
watch(
    () => [form.payment_status, form.deal_price],
    ([newStatus, newDealPrice]) => {
        if (newStatus === 'LUNAS') {
            form.paid_amount = newDealPrice;
        } else if (newStatus === 'DP' && form.paid_amount === newDealPrice) {
            form.paid_amount = 0;
        }
    }
);

// Auto-Pindah Tab jika Sales ubah Status & Suhu
watch(
    () => [form.status, form.temperature],
    ([newStatus, newTemp]) => {

        // Jika Sales ubah status jadi Closing & Suhu HOT, paksa pindah ke Tab Uang!
        if (newStatus === 'CLOSED_WON' && newTemp === 'HOT') {
            activeTab.value = 'convert_lead';
        }
        // Jika Sales batalin closing (kembali ke Follow Up), sembunyikan tab uang
        else if (activeTab.value === 'convert_lead') {
            activeTab.value = 'prospect';
        }
    }
);
const fillForm = (data) => {
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

        // Reset field konversi
        deal_price: 0,
        paid_amount: 0,
        payment_status: 'LUNAS',
        payment_method: 'cash',
        quantity: '',

        bank_name: '',
        notes_sales: ''
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
        role_name: '',

        deal_price: 0,
        paid_amount: 0,
        payment_status: 'LUNAS',
        payment_method: 'cash',
        quantity: '',

        bank_name: '',
        notes_sales: ''
    })
    form.reset();
    form.clearErrors();
    dataLead.value = null
}
const tabList = computed(() => {
    let tabs = [];

    // Mode Edit: Activity ditaruh paling depan biar Sales fokus ngetik laporan
    if (isEdit.value) {
        tabs.push({ id: 'activity', name: '💬 Aktivitas Follow-up', badge: dataLead.value?.activity?.length || 0 });
        tabs.push({ id: 'prospect', name: '📋 Data Prospek' });
    } else {
        tabs.push({ id: 'prospect', name: '📋 Data Prospek' });
    }

    //Tab Konversi Uang HANYA diselipkan jika statusnya Deal/Closing
    if (form.status === 'CLOSED_WON' && form.temperature === 'HOT') {
        tabs.push({ id: 'convert_lead', name: '💰 Data Konversi' });

    }

    // Mode Create: Activity ditaruh paling belakang
    if (!isEdit.value) {
        tabs.push({ id: 'activity', name: '💬 Aktivitas Follow-up' });
    }
    return tabs;
})

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

    const transformData = (data) => {
        const payload = {
            ...data,
            ...(isEdit.value && { _method: 'put' }),
        };

        // Apakah sedang melakukan closing?
        const isClosing =
            payload.temperature === 'HOT' &&
            payload.status === 'CLOSED_WON';

        // Jika bukan closing, kosongkan field transaksi
        if (!isClosing) {
            delete payload.deal_price;
            delete payload.paid_amount;
            delete payload.payment_status;
            delete payload.payment_method;
            delete payload.quantity;
            delete payload.bank_name;
            delete payload.notes_sales;
        }
        return payload;
    }

    form.transform(transformData).post(url, {
        preserveScroll: true,
        preserveState: true,
        replace: true,

        onSuccess: () => {
            close();
            form.reset();
        },
        onError: (errors) => {
            const prospectFields = [
                'name',
                'phone',
                'email',
                'id_channel',
                'id_product',
                'temperature',
                'status',
                'follow_up_date',
                'notes',
            ];
            const convertFields = ['deal_price', 'paid_amount', 'payment_status', 'payment_method', 'deal_at', 'notes_sales'];

            const isErrorInProspect = Object.keys(errors).some(field => prospectFields.includes(field));
            const isErrorInConvert = Object.keys(errors).some(field => convertFields.includes(field));

            // Auto-redirect ke tab yang error
            if (isErrorInConvert && form.status === 'CLOSED_WON' && form.temperature === 'HOT' && activeTab.value !== 'convert_lead') {
                activeTab.value = 'convert_lead';

            }
            else if (isErrorInProspect && activeTab.value !== 'prospect') {
                activeTab.value = 'prospect';
            }
            else if (errors.new_activity && activeTab.value !== 'activity') {
                activeTab.value = 'activity';
            }
        }
    });
};
// watch untuk kondisi buka modal, jika props.show berubah menjadi true, maka loadLead akan dijalankan
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
const emit = defineEmits(["update:show", "saved"]);
const close = () => {
    emit("update:show", false);
    resetForm();
    isChangingProduct.value = false;
}
const submitTextButton = computed(() => {
    if (form.processing) return 'Memproses...';
    if (form.status === 'CLOSED_WON' && form.temperature === 'HOT') return 'Proses Closing';
    return isEdit.value ? 'Simpan Perubahan' : 'Simpan Prospek';
});
const getRoleName = (data, mode = 'create') => {
    let role;
    if (mode === 'create') {
        role = data.user.roles[0].name
    }
    if (mode === 'edit') {
        role = data.user_update.roles[0].updated_by_name
    }
    // const role = data.user.roles.map(role => role.name)
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

// kunci field status dan temperature jika sudah pernah disahkan menjadi prospek HOT & CLOSING
const isLocked = computed(() => {
    return isEdit.value && dataLead.value?.status === 'CLOSED_WON' && dataLead.value?.temperature === 'HOT';
});

const prospectOptions = computed(() => {
    let options = [
        { label: '⭐ Baru Masuk', value: 'NEW' },
        { label: '💬 Sedang Follow Up', value: 'FOLLOW_UP' },
        { label: '❌ Lost / Batal', value: 'CLOSED_LOST' },
        { label: '✔️ Closing (Terjual)', value: 'CLOSED_WON' },
    ];
    if (isEdit.value) {
        options =
            [
                { label: '⭐ Baru Masuk', value: 'NEW' },
                { label: '💬 Sedang Follow Up', value: 'FOLLOW_UP' },
                { label: '❌ Lost / Batal', value: 'CLOSED_LOST' },
            ]
    }

    return options
})
const tempOptions = computed(() => {
    let options = [
        { label: '🔥 HOT (Siap Bayar)', value: 'HOT' },
        { label: '⏳ WARM (Tanya-tanya)', value: 'WARM' },
        { label: '❄️ COLD (Sekedar Lewat)', value: 'COLD' },
    ];
    if (isEdit.value) {
        options =
            [
                { label: '⏳ WARM (Tanya-tanya)', value: 'WARM' },
                { label: '❄️ COLD (Sekedar Lewat)', value: 'COLD' },
            ]
    }
    return options;
})
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

                    <alert-error-all :errors="form.errors" @close="form.clearErrors()" />

                    <div class="celestial-tab-container mb-4">
                        <button v-for="tab in tabList" :key="tab.id" type="button" @click="activeTab = tab.id"
                            class="celestial-tab-btn" :class="{ 'active': activeTab === tab.id }">
                            <span>{{ tab.name }}</span>
                            <span v-if="tab.badge" class="badge rounded-pill bg-primary ms-2"
                                style="font-size: 10px;">{{ tab.badge }}</span>
                        </button>
                    </div>

                    <form-wrapper @submit.prevent="submit">
                        <div v-show="activeTab === 'prospect'" class="row g-2 animate-fade-in">
                            <div class="col-md-6 mb-2">
                                <label for="name_input" class="form-label small fw-bold text-muted">NAMA PROSPEK
                                    *</label>
                                <input-text :error="form.errors.name" v-model="form.name" name="name"
                                    placeholder="Cth: Budi Santoso" />
                                <input-error :message="form.errors.name" />
                            </div>

                            <div class="col-md-6 mb-2">
                                <label for="phone_input" class="form-label small fw-bold text-muted">NO. HANDPHONE / WA
                                    *</label>
                                <input-number :maxChar="15" :error="form.errors.phone" v-model="form.phone" name="phone"
                                    placeholder="081233445566" />
                                <input-error :message="form.errors.phone" />
                            </div>

                            <div class="col-md-6 mb-2">
                                <label for="email_input" class="form-label small fw-bold text-muted">EMAIL
                                    PELANGGAN (Opsional)</label>
                                <input-text :error="form.errors.email" v-model="form.email" name="email"
                                    placeholder="Cth: dimas@perusahaan.com" />
                                <input-error :message="form.errors.email" />
                            </div>

                            <div class="col-md-6 mb-2">
                                <label for="channels_select" class="form-label small fw-bold text-muted">SUMBER
                                    TRAFIK
                                    (CHANNEL) *</label>
                                <select-input text="-- Pilih Channel --" name="channels" v-model="form.id_channel"
                                    :error="form.errors.id_channel" :options="channelOptions" />
                                <input-error :message="form.errors.id_channel" />
                            </div>

                            <div class="col-md-12 mb-2">
                                <div class="d-flex justify-content-between">
                                    <label for="product_select" class="form-label small fw-bold text-muted">PRODUK
                                        YANG
                                        DIMINATI</label>

                                    <span v-if="form.id_product && !isChangingProduct && !isLocked"
                                        @click="isChangingProduct = true" class="small fw-bold text-primary"
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

                            <div class="col-md-4 mb-2">
                                <label for="temp_select" class="form-label small fw-bold text-muted">SUHU
                                    KETERTARIKAN</label>
                                <select-input :disabled="isLocked" text="-- Pilih Suhu --" name="temp"
                                    v-model="form.temperature" :error="form.errors.temperature"
                                    :selectClass="{ 'text-danger': form.temperature === 'COLD', 'text-warning': form.temperature === 'WARM', 'text-success': form.temperature === 'HOT' }"
                                    :options="tempOptions" />
                                <input-error :message="form.errors.temperature" />
                            </div>

                            <div class="col-md-4 mb-2">
                                <label for="status_select" class="form-label small fw-bold text-muted">STATUS
                                    PROSPEK</label>
                                <select-input :disabled="isLocked" text="-- Pilih Status --" v-model="form.status"
                                    name="status" :error="form.errors.status" :options="prospectOptions" />
                                <input-error :message="form.errors.status" />
                            </div>

                            <div class="col-md-4 mb-2">
                                <label for="follow_up_date_input" class="form-label small fw-bold text-muted">JADWAL
                                    FOLLOW UP (Opsional)</label>
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

                        <div v-show="activeTab === 'convert_lead'" class="row g-3 animate-fade-in">

                            <div v-if="isLocked" class="col-12">
                                <div class="alert alert-info border-0 shadow-sm p-3">
                                    <i class="bi bi-lock-fill me-2"></i>
                                    <strong>Data Transaksi Terkunci!</strong><br>
                                    <small>Data ini sudah sah menjadi penjualan. Perubahan data keuangan harus melalui
                                        menu Penjualan/Invoice.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label for="deal_price_input" class="form-label small fw-bold text-muted">HARGA
                                    DEAL*</label>
                                <input-currency :disabled="isLocked" inputClass="text-success" :decimals="0"
                                    name="deal_price" :error="form.errors.deal_price" v-model="form.deal_price"
                                    placeholder="0" />
                                <input-error :message="form.errors.deal_price" />
                                <small class="text-muted d-block mt-1">Terbilang: <strong>{{
                                    currencyFormat(form.deal_price)
                                        }}</strong></small>
                            </div>

                            <div class="col-md-4">
                                <label for="paid_amount_input" class="form-label small fw-bold text-muted">UANG MASUK
                                    SEKARANG *</label>
                                <input-currency :disabled="isLocked" :readonly="form.payment_status === 'LUNAS'"
                                    :error="form.errors.paid_amount" :decimals="0" name="paid_amount"
                                    v-model="form.paid_amount" placeholder="0" />
                                <input-error :message="form.errors.paid_amount" />
                                <small v-if="form.deal_price - form.paid_amount > 0"
                                    class="text-danger mt-1 d-block fw-medium" style="font-size: 11px;">
                                    Sisa Tagihan: {{ currencyFormat(form.deal_price - form.paid_amount) }}
                                </small>
                            </div>
                            <div class="col-md-4">
                                <label for="quantity_input" class="form-label small fw-bold text-muted">QUANTITY
                                    *</label>
                                <input-number :disabled="isLocked" :maxChar="5" :error="form.errors.quantity"
                                    name="quantity" v-model="form.quantity" placeholder="0" />
                                <input-error :message="form.errors.quantity" />
                            </div>

                            <div class="col-md-6">
                                <label for="payment_status_select" class="form-label small fw-bold text-muted">STATUS
                                    BAYAR
                                    *</label>
                                <select-input :disabled="isLocked" name="payment_status" v-model="form.payment_status"
                                    :error="form.errors.payment_status" :options="[
                                        { label: '✅ LUNAS Penuh', value: 'LUNAS' },
                                        { label: '⏳ Uang Muka (DP)', value: 'DP' },
                                    ]" />
                                <input-error :message="form.errors.payment_status" />
                            </div>

                            <div class="col-md-6">
                                <label for="payment_method_select" class="form-label small fw-bold text-muted">METODE
                                    PEMBAYARAN *</label>
                                <select-input :disabled="isLocked" name="payment_method" v-model="form.payment_method"
                                    :error="form.errors.payment_method" :options="[
                                        { label: '🏦 Tunai / Cash', value: 'cash' },
                                        { label: '🏦 Transfer', value: 'transfer' },
                                        { label: '📱 Debit', value: 'debit' },
                                        { label: '💵 QRIS', value: 'qris' }
                                    ]" />
                                <input-error :message="form.errors.payment_method" />
                            </div>

                            <div v-if="form.payment_method === 'transfer' || form.payment_method === 'debit'"
                                class="col-12">
                                <label class="form-label small fw-bold text-muted mb-2">NAMA BANK*</label>
                                <input-text name="bank_name" v-model="form.bank_name" :error="form.errors.bank_name"
                                    placeholder="Cth: BCA, Mandiri, BNI Dll" />
                                <input-error :message="form.errors.bank_name" />
                            </div>

                            <div class="col-12 mt-3">
                                <label for="notes_sales_input" class="form-label small fw-bold text-muted">CATATAN
                                    TRANSAKSI
                                    (Opsional)</label>
                                <text-area :disabled="isLocked" :maxChar="500" :error="form.errors.notes_sales"
                                    name="notes_sales" v-model="form.notes_sales" :rows="2"
                                    placeholder="Cth: Pelanggan minta jika pembelian barang banyak ada pengurangan harga..." />
                                <input-error :message="form.errors.notes_sales" />
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
                                                    class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-1">
                                                    <div class="d-flex flex-column small">
                                                        <div class="fw-bold  text-success text-capitalize"
                                                            style="font-size: 14px;">
                                                            <i class="bi bi-plus-circle"></i>
                                                            Dibuat: {{ act.user?.activity_creator_name || 'Sistem'
                                                            }} -
                                                            {{ getRoleName(act, 'create').label }}
                                                        </div>
                                                        <div v-if="act.created_at !== act.updated_at"
                                                            class="fw-bold mt-1 text-capitalize">
                                                            <i class="bi bi-pencil-square text-warning"></i>
                                                            Diubah:
                                                            {{ act.user_update?.name }}
                                                            -
                                                            {{ getRoleName(act, 'edit').label }}
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-muted" style="font-size: 12px;">
                                                            <i class="bi bi-plus-circle"></i>
                                                            {{ formatDate(act.created_at) }}
                                                        </span>
                                                        <span v-if="act.created_at !== act.updated_at"
                                                            class="text-muted" style="font-size: 12px;">
                                                            <i class="bi bi-pencil-square"></i>
                                                            {{ formatDate(act.updated_at) }}
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
                                                            class="text-primary rounded-pill cursor-pointer badge badge-soft-success bg-gradient">
                                                            {{ activityEdit.id === act.id_lead_activity ? 'Simpan' :
                                                                'Ubah'
                                                            }}
                                                        </span>
                                                        <span v-if="activityEdit.id === act.id_lead_activity"
                                                            @click.prevent="cancelEditActivity"
                                                            class="badge rounded-pill badge-soft-danger cursor-pointer">Batal</span>

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

                        <button @click.prevent="submit" type="button"
                            :disabled="form.processing || isLoading || isLocked" class="btn btn-height-1 fw-semibold"
                            :class="form.status === 'CLOSED_WON' ? 'btn-c-success' : 'btn-c-primary'">
                            <span v-if="isLocked"><i class="bi bi-lock-fill me-1"></i> Data Terkunci</span>
                            <span v-else>
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else :class="isEdit ? 'bi bi-check-circle me-1' : 'bi bi-save me-1'"></i>
                                {{ submitTextButton }}
                            </span>
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
