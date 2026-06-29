import { reactive } from "vue";

export function useRowAlert() {
    const __alert = reactive({
        id: null,
        type: "success",
        message: "",
        visible: false,
    });

    let timer = null;

    const show = ({ id, message, type = "success", duration = 3000 }) => {
        clearTimeout(timer);
        Object.assign(__alert, {
            id,
            type,
            message,
            visible: true,
        });

        timer = setTimeout(() => {
            hide();
        }, duration);
    };

    const hide = () => {
        __alert.id = null;
        __alert.message = "";
        __alert.type = "success";
        __alert.visible = false;
    };

    return {
        __alert,
        show,
        hide,
    };
}
