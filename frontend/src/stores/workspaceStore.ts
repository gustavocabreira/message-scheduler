import { workspaceService } from "@/services/workspaceService";
import type { Workspace } from "@/types/workspace";
import { defineStore } from "pinia";
import { ref } from "vue";
import { useUserStore } from "@/stores/userStore";

export const useWorkspaceStore = defineStore("workspace", () => {
    const workspaces = ref<Workspace[]>([]);
    const activeWorkspace = ref<Workspace | null>(null);
    const userStore = useUserStore();

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

    async function fetchActiveWorkspace() {
        const res = await workspaceService.getActiveWorkspace();

        activeWorkspace.value = res.ok && res.data ? res.data : null;

        return res;
    }

    async function activateWorkspace(workspace: Workspace) {
        const res = await workspaceService.activateWorkspace(workspace.id);

        if (res.ok && res.data) {
            activeWorkspace.value = res.data;
            workspaces.value = workspaces.value.map((item) =>
                item.id === res.data!.id ? { ...item, role: res.data!.role } : item
            );

            const currentUser = userStore.getUser();

            if (currentUser !== null) {
                userStore.setUser({
                    ...currentUser,
                    role: res.data.role,
                });
            }
        }

        return res;
    }

    return {
        workspaces,
        activeWorkspace,
        fetchWorkspaces,
        fetchActiveWorkspace,
        activateWorkspace,
        getWorkspaces,
    };
})