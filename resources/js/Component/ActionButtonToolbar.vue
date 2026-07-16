<script setup>
import { computed } from 'vue';
import { hasPermission, hasRole } from "@/Composables/useAuth";
import { usePage } from '@inertiajs/vue3';
const props = defineProps({
    item: {
        type: [Object, Array, String, Number, Boolean],
        required: true,
    },
    actions: {
        type: Array,
        required: true,
    },
    noActionText: {
        type: String,
        default: 'No Action',
    },

    showNoAction: {
        type: Boolean,
        default: true,
    },
    justify: {
        type: String,
        default: 'center', // center, start, end
    },

    gap: {
        type: String,
        default: '1',
    },
});

// Emit event
const emit = defineEmits();

const page = usePage();

const authUserId = computed(() => {
    return page.props.auth?.user_id ?? page.props.auth?.user?.id ?? null;
});

const isCurrentLoggedUser = (item) => {
    return String(item?.id) === String(authUserId.value);
};

const isSuperAdminRow = (item) => {
    if (!item) return false;

    return item.role_name === 'super_admin';
};

const isDeveloper = computed(() => hasRole('developer'));
const isSuperAdmin = computed(() => hasRole('super_admin'));
const isSupervisor = computed(() => hasRole('supervisor'));


/**
 * Filter action berdasarkan permission, role, show.
 */
const visibleActions = computed(() => {
    return props.actions
        .map(action => {
            /**
             * SUPER ADMIN
             * tidak boleh edit / hapus dirinya sendiri
             */
            // if (
            //     isSuperAdmin.value &&
            //     isCurrentLoggedUser(props.item) &&
            //     action.protectedSelf
            // ) {
            //     return null;
            // }

            /**
            * SUPERVISOR
            * tidak boleh edit / hapus dirinya sendiri
            */

            // if (
            //     isSupervisor.value &&
            //     isCurrentLoggedUser(props.item) &&
            //     action.protectedSelf
            // ) {
            //     return null;
            // }
            if (hasRole(['developer', 'super_admin', 'supervisor']) && isCurrentLoggedUser(props.item) && action.protectedSelf) {
                return null
            }
            const canShow =
                typeof action.show === 'function'
                    ? action.show(props.item)
                    : action.show !== false;

            if (!canShow) {
                return null;
            }

            if (action.permission && !hasPermission(action.permission)) {
                return null;
            }

            const canDisable =
                typeof action.disabled === 'function'
                    ? action.disabled(props.item)
                    : action.disabled !== false;

            if (!canDisable) {
                return null;
            }

            if (action.role && !hasRole(action.role)) {
                return null;
            }




            return action;
        })
        .filter(Boolean);
});
// "d-flex gap-1 align-items-center justify-content-center
const wrapperClass = computed(() => {
    return [
        'd-flex',
        'align-items-center',
        `justify-content-${props.justify}`,
        `gap-${props.gap}`,
    ];
});
const handleClick = (action) => {
    if (action.disabled) return;

    emit(action.action, props.item);
};
</script>
<template>
    <div v-if="visibleActions.length > 0" :class="wrapperClass">
        <button v-for="(action, index) in visibleActions" :key="index" type="button" :class="[
            action.buttonClass || 'btn-action',
            action.variant || '',
            action.class || '',
            action.disabled ? 'opacity-50 cursor-not-allowed' : '',
        ]" :disabled="action.disabled" :title="action.tooltip || action.title || action.label || ''"
            @click.prevent="handleClick(action)">
            <i v-if="action.icon" :class="[
                action.icon,
                action.iconClass || '',
                action.label ? 'me-0' : '',
            ]"></i>

            <span v-if="action.showLabel">
                {{ action.label }}
            </span>
        </button>
    </div>

    <div v-else-if="showNoAction" class="text-center d-flex justify-content-center">
        <span class="badge bg-light text-muted border rounded-pill px-3 no-action-badge">
            <i class="bi bi-lock me-1"></i>
            {{ noActionText }}
        </span>
    </div>
</template>
<style scoped>
.cursor-not-allowed {
    cursor: not-allowed;
}

.no-action-badge {
    font-size: 14px;
}
</style>
