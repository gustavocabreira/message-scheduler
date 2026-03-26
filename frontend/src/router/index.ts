import { createRouter, createWebHistory } from 'vue-router'
import routes from './routes'
import { useUserStore } from '@/stores/userStore';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: routes,
});

router.beforeEach(async (to) => {
  const userStore = useUserStore();
  const requiresAuth = to.meta.requiresAuth;
  const authRoutes = ["Login", "RedirectCallback"];

  if (userStore.getUser() && authRoutes.includes(to.name as string)) {
    return { name: "Dashboard" };
  }

  if (!userStore.getUser() && requiresAuth && to.name !== "RedirectCallback") {
    return { name: "Login" };
  }

  return true;
});

export default router
