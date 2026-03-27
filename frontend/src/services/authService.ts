import { http } from "@/services/http";

export const authService = {
    async logout() {
        return await http.request<null>("POST", "/auth/logout");
    }
}
