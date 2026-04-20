import { ref } from 'vue';

const isAiPanelOpen = ref(false);

export function useSidePanels() {
    const toggleAiPanel = () => {
        isAiPanelOpen.value = !isAiPanelOpen.value;
    };

    const openAiPanel = () => {
        isAiPanelOpen.value = true;
    };

    const closeAiPanel = () => {
        isAiPanelOpen.value = false;
    };

    return {
        isAiPanelOpen,
        toggleAiPanel,
        openAiPanel,
        closeAiPanel,
    };
}
