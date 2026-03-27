import { http } from "@/services/http";
import type { User } from "@/types/user";

export const userService = {
  async getCurrentUser() {
    return await http.request<User>("GET", "/me");
  },
};
