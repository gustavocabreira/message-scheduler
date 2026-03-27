import type { Workspace } from "@/types/workspace"
import { http } from "./http"

export const workspaceService = {
    async getWorkspaces() {
        return await http.request<Workspace[]>("GET", "/v1/workspaces");
    }
}