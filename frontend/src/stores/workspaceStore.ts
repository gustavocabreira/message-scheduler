import { workspaceService } from "@/services/workspaceService";
import type { Workspace } from "@/types/workspace";
import { defineStore } from "pinia";
import { ref } from "vue";

export const useWorkspaceStore = defineStore("workspace", () => {
    const workspaces = ref<Workspace[]>([]);

    function getWorkspaces() {
        return workspaces.value;
    }

    async function fetchWorkspaces() {
        const res = await workspaceService.getWorkspaces();

        if (res.ok && res.data) {
            workspaces.value = res.data;
        } else {
            workspaces.value = [];
        }

        return res;
    }

    return {
        workspaces,
        fetchWorkspaces,
        getWorkspaces,
    };
})