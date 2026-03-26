import LoginView from '@/views/auth/LoginView.vue'
import RedirectCallbackView from '@/views/auth/RedirectCallbackView.vue'
import DashboardView from '@/views/DashboardView.vue'
import AppLayoutView from '@/views/layouts/AppLayoutView.vue'
import GuestLayoutView from '@/views/layouts/GuestLayoutView.vue'

const routes = [
    {
        path: "/",
        component: AppLayoutView,
        children: [
            {
                path: "",
                component: DashboardView,
                name: "Dashboard",
                meta: { requiresAuth: true },
            },
        ],
    },
    {
        path: "/auth",
        component: GuestLayoutView,
        children: [
            {
                path: "login",
                name: "Login",
                component: LoginView,
                meta: { requiresAuth: false },
            },
            {
                path: "callback",
                name: "RedirectCallback",
                component: RedirectCallbackView,
                meta: { requiresAuth: false },
            },
        ],
    },
]

export default routes