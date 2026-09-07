import { ref } from "vue";
const unreadCount = ref(0);

export function useNotification() {
    const setUnreadCount = (count) => {
        unreadCount.value = Number(count) || 0;
    };

    const incrementUnread = (amount = 1) => {
        unreadCount.value += amount;
    };

    const decrementUnread = (amount = 1) => {
        unreadCount.value = Math.max(0, unreadCount.value - amount);
    };
    const resetUnread = () => {
        unreadCount.value = 0;
    };

    return {
        unreadCount,
        setUnreadCount,
        incrementUnread,
        decrementUnread,
        resetUnread,
    };
}
