import { http } from "@/services/http";
import type { User } from "@/types/user";
import axios from "axios";

export const userService = {
  async getCurrentUser() {
    return await http.request<User>("GET", "/me");
  },

  async getCsfrCookie() {
    await axios.get(import.meta.env.VITE_API_URL + "/sanctum/csrf-cookie", {
      withCredentials: true,
      withXSRFToken: true,
    });
  }
};
