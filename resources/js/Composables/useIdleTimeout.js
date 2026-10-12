import { onMounted, onBeforeUnmount } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

export function useIdleTimeout({
    timeoutSeconds = 3600, // Default timeout of 1 hour
    lastActivityAt = null,
} = {}) {
    const PING_INTERVAL = 30_000;

    let timer = null;
    let lastPingAt = 0;
    let lastActivity = Number(lastActivityAt) || Math.floor(Date.now() / 1000);

    let expired = false;
    let pingInProgress = false;
    let pingPending = false;

    const nowSeconds = () => Math.floor(Date.now() / 1000);

    const clearIdleTimer = () => {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    };

    const expireSession = () => {
        if (expired) return;

        expired = true;
        clearIdleTimer();

        router.post(
            route("logout"),
            {},
            {
                onFinish: () => {
                    window.location.assign(route("login"));
                },
                onError: () => {
                    window.location.assign(route("login"));
                },
            }
        );
    };

    const resetIdleTimer = () => {
        clearIdleTimer();

        const elapsed = nowSeconds() - lastActivity;
        const remaining = timeoutSeconds - elapsed;

        if (remaining <= 0) {
            expireSession();
            return;
        }

        timer = setTimeout(() => {
            // Periksa lagi jika timer browser terlambat.
            if (nowSeconds() - lastActivity >= timeoutSeconds) {
                expireSession();
            } else {
                resetIdleTimer();
            }
        }, remaining * 1000);
    };

    const sendActivityPing = async () => {
        if (expired) return;

        const now = Date.now();

        if (pingInProgress) {
            pingPending = true;
            return;
        }

        // Batasi ping ketika pengguna terus bergerak.
        if (now - lastPingAt < PING_INTERVAL) {
            return;
        }

        pingInProgress = true;
        lastPingAt = now;

        try {
            await axios.post(route("activity.ping"));
        } catch (error) {
            if (
                error.response?.status === 401 ||
                error.response?.status === 419
            ) {
                expired = true;
                clearIdleTimer();
                window.location.assign(route("login"));
            }
        } finally {
            pingInProgress = false;

            if (pingPending && !expired) {
                pingPending = false;

                // Kirim ulang hanya jika masih ada aktivitas baru.
                if (Date.now() - lastPingAt >= PING_INTERVAL) {
                    sendActivityPing();
                }
            }
        }
    };

    const handleActivity = () => {
        if (expired) return;

        lastActivity = nowSeconds();

        resetIdleTimer();
        sendActivityPing();
    };

    const activityEvents = [
        "pointerdown",
        "keydown",
        "scroll",
        "touchstart",
        "mousemove",
    ];

    onMounted(() => {
        resetIdleTimer();

        activityEvents.forEach((event) => {
            window.addEventListener(event, handleActivity, { passive: true });
        });
    });

    onBeforeUnmount(() => {
        clearIdleTimer();

        activityEvents.forEach((event) => {
            window.removeEventListener(event, handleActivity);
        });
    });
}
