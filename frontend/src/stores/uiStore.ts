import { defineStore } from "pinia";
import { ref } from "vue";

export const useUiStore = defineStore("ui", () => {
    const loadingVisible = ref(false);
    const loadingMessage = ref("Loading...");

    async function withLoadingOverlay<T>(message: string, fn: () => Promise<T>): Promise<T> {
        loadingMessage.value = message;
        loadingVisible.value = true;
        try {
            return await fn();
        } finally {
            loadingVisible.value = false;
        }
    }

    return {
        loadingVisible,
        loadingMessage,
        withLoadingOverlay,
    };
});
