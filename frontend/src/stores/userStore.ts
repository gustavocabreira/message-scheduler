import { ref } from "vue";
import { defineStore } from "pinia";
import type { User } from "@/types/user";
import { userService } from "@/services/userService";

export const useUserStore = defineStore("user", () => {
  const user = ref<User | null>(null);

  function setUser(next: User | null) {
    user.value = next;
  }

  function getUser(): User | null {
    return user.value ?? null;
  }

  function clearUser() {
    user.value = null;
  }

  async function fetchUser() {
    const res = await userService.getCurrentUser();

    if (res.ok && res.data) {
      setUser(res.data);
    } else {
      setUser(null);
    }

    return res;
  }

  return {
    // state
    user,

    // actions
    setUser,
    getUser,
    clearUser,
    fetchUser,
  };
});
