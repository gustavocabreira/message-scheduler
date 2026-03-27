import { workspaceService } from "@/services/workspaceService";
import type { Workspace } from "@/types/workspace";
import { defineStore } from "pinia";
import { ref } from "vue";
import { useUserStore } from "@/stores/userStore";

const ACTIVE_WORKSPACE_KEY = "active_workspace";

export const useWorkspaceStore = defineStore("workspace", () => {
    const workspaces = ref<Workspace[]>([]);
    const activeWorkspace = ref<Workspace | null>(
        JSON.parse(localStorage.getItem(ACTIVE_WORKSPACE_KEY) ?? "null")
    );
    const switching = ref(false);
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
        if (activeWorkspace.value) {
            localStorage.setItem(ACTIVE_WORKSPACE_KEY, JSON.stringify(activeWorkspace.value));
        }

        return res;
    }

    async function activateWorkspace(workspace: Workspace) {
        switching.value = true;
        const res = await workspaceService.activateWorkspace(workspace.id);

        if (res.ok && res.data) {
            activeWorkspace.value = res.data;
            localStorage.setItem(ACTIVE_WORKSPACE_KEY, JSON.stringify(res.data));
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

        switching.value = false;
        return res;
    }

    return {
        workspaces,
        activeWorkspace,
        switching,
        fetchWorkspaces,
        fetchActiveWorkspace,
        activateWorkspace,
        getWorkspaces,
    };
})