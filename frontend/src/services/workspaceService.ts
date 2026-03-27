import type { Workspace } from "@/types/workspace"
import { http } from "./http"

export const workspaceService = {
    async getWorkspaces() {
        return await http.request<Workspace[]>("GET", "/v1/workspaces");
    },

    async getActiveWorkspace() {
        return await http.request<Workspace>("GET", "/v1/workspace/active");
    },

    async activateWorkspace(workspaceId: number) {
        return await http.request<Workspace>("POST", `/v1/workspaces/${workspaceId}/activate`);
    },
}