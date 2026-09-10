<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import { hasRole } from '@/Composables/useAuth';
import { currencyFormat } from "@/Helpers/currencyFormat";
import axios from "axios";
const props = defineProps({
    idSales: {
        type: String,
        default: null
    },
    show: Boolean,
});

const dataSales = ref(null);
const emit = defineEmits(["update:show", "saved"]);
const close = () => {
    emit("update:show", false);
    resetForm();
    dataSales.value = null;
}

const form = useForm({
    payment_method: null,
    bank_name: '',
    paid_amount: 0,
})
const fillForm = (data) => {
    console.log(data);
    form.defaults({
        paid_amount: data.paid_amount,
    })

    dataSales.value = data
    form.reset();
}
const resetForm = () => {
    form.reset();
    form.clearErrors();
}
const loadSales = async () => {

    if (!props.idSales) {
        resetForm();
        return
    }

    try {
        const { data } = await axios.get(`/sales/show/${props.idSales}`);
        fillForm(data);
    } catch (error) {
        console.error("Gagal mengambil data:", error);
        alert("Terjadi kesalahan saat memuat data.");
    }
}
const submit = () => {
    form.post(`/sales/cancel/${props.idSales}`, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onSuccess: () => {
            emit("saved");
            close();
        }
    })
}
watch(() => props.show, async (isOpen) => {
    if (!isOpen) return;
    if (props.idSales) {
        await loadSales();
    } else {
        resetForm();
    }
})
</script>
<template>
    <div class="row" v-if="props.show">
        <div class="col-12">
            <modal width="600px" size="modal-lg" :icon="['text-danger', 'bi bi-x-octagon']" :show="props.show"
                title="Pembatalan Pembayaran" :mobileFullscreen="false" @closed="close">

                <template #body>
                    <alert-error-all :errors="form.errors" @close="form.clearErrors()" />



                </template>

                <template #footer>
                    <div class="d-flex justify-content-end gap-2 p-2 bg-transparent">

                        <button type="button" class="btn btn-c-slate border-0 fw-medium px-4 btn-height-1"
                            @click.prevent="close">Batal</button>

                        <button @click="submit" type="button" :disabled="form.processing"
                            class="btn btn-danger fw-bold shadow-sm btn-height-1">
                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="bi bi-x-circle me-1"></i> BATALKAN PEMBAYARAN
                        </button>

                    </div>
                </template>

            </modal>
        </div>
    </div>
</template>
