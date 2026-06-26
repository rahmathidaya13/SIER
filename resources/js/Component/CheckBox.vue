<script setup>
import { computed } from "vue";

const emit = defineEmits(["update:checked"]);
const props = defineProps({
    checked: {
        type: [Array, Boolean, String, Number,Object],
        required: true,
    },
    value: {
        default: null,
    },
    name: {
        type: String,
        default: "",
    },

    label: {
        type: String,
        default: ""
    },
    inputClass: {
        type: String,
        default: ""
    }
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },
    set(value) {
        emit("update:checked", value);
    },
});
</script>
<template>
    <div class="form-check">
        <input type="checkbox" :class="['form-check-input', inputClass]" v-model="proxyChecked" :value="value"
            :name="props.name" :id="props.name" />
        <label v-if="label" class="form-check-label" :for="props.name">
            <slot> {{ label }} </slot>
        </label>
    </div>
</template>
